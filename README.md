# GPlayNation

GPlayNation is a storefront for Indonesian local games: players browse and buy games,
developers upload and manage their catalogue, and admins verify studios and moderate releases.
Built with Laravel 12 + Blade + Tailwind CSS (v4) + Vite.

## Requirements

- Docker Engine with Compose v2
- Node.js is **not** required for the Docker deployment; assets are built inside the image

## Docker deployment

Everything runs in one container named **`gpn-serve`**. Supervisor keeps four
processes alive inside it:

| Process     | Role                                              |
|-------------|---------------------------------------------------|
| `nginx`     | serves the app and static assets                  |
| `php-fpm`   | runs Laravel                                      |
| `queue`     | `php artisan queue:work` (database queue)         |
| `scheduler` | `php artisan schedule:work` (daily job)           |

The SQLite database, uploaded covers/installers, and cache/log directories live
in the `gplaynation_storage` Docker volume. Migrations and Laravel caches are
built once when `gpn-serve` starts.

### 1. Configure the environment

```bash
cp .env.docker.example .env.docker
echo "base64:$(openssl rand -base64 32)"   # copy the output into APP_KEY=
```

Edit `.env.docker`:

- `APP_KEY` — paste the generated key
- `APP_URL` — the public URL; the local default is `http://localhost:8001`
- `GPN_PAYMENT_SERVER_KEY` — replace the placeholder with a real secret
- `SESSION_SECURE_COOKIE` — set to `true` when serving over HTTPS

`.env.docker` contains secrets and is ignored by git. It is separate from the
Laravel `.env` used for non-Docker local development.

### 2. Start the container

```bash
docker compose up -d --build
```

Then open <http://localhost:8001>. The container name is fixed, so the usual
commands work directly:

```bash
docker logs -f gpn-serve
docker stop gpn-serve
docker start gpn-serve
```

To publish a different host port (for example `8000`), set `APP_PORT`:

```bash
APP_PORT=8000 docker compose up -d
```

#### Plain `docker run` alternative

If you prefer not to use Compose, build the image once and run the same
container:

```bash
docker build -f docker/Dockerfile -t gplaynation:latest .

docker run -d --name gpn-serve --restart unless-stopped \
  --env-file .env.docker \
  -p 8001:80 \
  -v gplaynation_storage:/var/www/html/storage \
  gplaynation:latest
```

The image's entrypoint reads `RUN_BOOTSTRAP=true` from `.env.docker` and runs
migrations before Supervisor starts nginx, php-fpm, the queue worker, and the
scheduler.

### 3. Seed the demo catalogue (optional)

Run the seeder once on a fresh database; re-running it creates duplicate demo
transactions and posts. It also generates the demo covers in the storage volume.
Run it after `gpn-serve` is healthy:

```bash
docker compose exec -u www-data gpn-serve php artisan db:seed --force

# equivalent without Compose
docker exec -u www-data gpn-serve php artisan db:seed --force
```

### 4. Operate it

```bash
docker compose ps                     # container health
docker logs -f gpn-serve              # nginx, php-fpm, queue, scheduler logs
docker compose up -d --build          # rebuild and restart after an update
docker compose down                   # remove container/network; volume kept
```

Migrations run automatically on startup (`RUN_MIGRATIONS=true`). Set it to
`false` in `.env.docker` if you prefer to run `php artisan migrate --force` as a
separate release step.

### Data and backups

The named volume `gplaynation_storage` contains the SQLite database
(`storage/app/database.sqlite`), uploaded game files, and cache/log directories.

```bash
# Back up (stop first so the SQLite files are consistent)
docker stop gpn-serve
docker run --rm \
  -v gplaynation_storage:/data \
  -v "$PWD":/backup \
  alpine tar czf /backup/gplaynation-storage-$(date +%F).tgz -C /data .
docker start gpn-serve

# Restore
docker compose down
docker run --rm \
  -v gplaynation_storage:/data \
  -v "$PWD":/backup \
  alpine sh -c 'rm -rf /data/* && tar xzf /backup/gplaynation-storage-YYYY-MM-DD.tgz -C /data'
docker compose up -d
```

### HTTPS and production notes

- Terminate TLS in front of the container (Caddy, Traefik, nginx, or a cloud
  load balancer) and reverse-proxy to `http://127.0.0.1:8001`. Set `APP_URL` to
  the HTTPS URL and `SESSION_SECURE_COOKIE=true`.
- Laravel already trusts proxy headers (`trustProxies(at: '*')`), so
  `X-Forwarded-*` headers from the proxy are honoured.
- The default database is SQLite with WAL enabled, which is suitable for a
  single-host deployment. For multiple hosts/replicas, switch `DB_*` in
  `.env.docker` to MySQL and run a MySQL service; the image includes `pdo_mysql`.
- Payment webhooks are exposed at `/webhooks/payment`. Keep
  `GPN_PAYMENT_SERVER_KEY` secret and configure the payment provider to call the
  public `APP_URL`.
- Container startup applies `php artisan optimize`. If you mount application
  code over the image for development, set `RUN_OPTIMIZE=false` and
  `RUN_MIGRATIONS=false` to avoid caching stale paths/config.
- For heavier queue loads, run additional `php artisan queue:work` processes or
  split the queue/scheduler into their own containers. The single-container
  layout is intended for straightforward single-host deployments.

## Local development (without Compose)

The runtime image can also be used as a PHP container while editing the code on
the host:

```bash
docker build -f docker/Dockerfile -t gplaynation:latest .

docker run --rm -v "$PWD":/app -w /app composer:2 install
cp .env.example .env
docker run --rm -v "$PWD":/app -w /app --entrypoint php gplaynation:latest artisan key:generate
touch database/database.sqlite
docker run --rm -v "$PWD":/app -w /app --entrypoint php gplaynation:latest artisan migrate --seed
docker run --rm -v "$PWD":/app -w /app --entrypoint php gplaynation:latest artisan storage:link

npm ci
npm run build

docker run --rm -v "$PWD":/app -w /app -p 8000:8000 --entrypoint php \
  gplaynation:latest artisan serve --host=0.0.0.0 --port=8000
```

Then open <http://localhost:8000>.

## Demo accounts

`php artisan db:seed --force` creates a full demo world (users, 12 published games
with covers + installers, transactions, community posts). All passwords are `password`:

| Email                     | Role                                    |
|---------------------------|-----------------------------------------|
| admin@gplaynation.test    | admin                                   |
| dev@gplaynation.test      | developer, verified (Studio Nusantara)  |
| dev2@gplaynation.test     | developer, verified (Arus Digital)      |
| pending@gplaynation.test  | developer, pending verification         |
| rejected@gplaynation.test | developer, rejected (Studio Bodong)     |
| user@gplaynation.test     | user                                    |
| user2@gplaynation.test    | user                                    |
| user3@gplaynation.test    | user                                    |

## Tests

Install dev dependencies into the host checkout, then run the suite through the
runtime image (it contains the required PHP extensions):

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 install
docker run --rm -v "$PWD":/app -w /app \
  -e APP_KEY="base64:$(openssl rand -base64 32)" \
  --entrypoint php gplaynation:latest artisan test
docker run --rm -v "$PWD":/app -w /app --entrypoint php gplaynation:latest vendor/bin/pint --test
```
