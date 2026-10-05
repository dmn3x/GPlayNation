<?php

namespace Database\Seeders;

use App\Enums\GameStatus;
use App\Models\Developer;
use App\Models\Game;
use App\Models\Iap;
use App\Models\Promotion;
use App\Models\Tag;
use App\Support\CoverArt;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    /**
     * @var array{title:string,genre:string,price:string}[] — first 12 are published.
     */
    private const GAMES = [
        ['title' => 'Pocong Runner', 'genre' => 'Horror', 'price' => '45000.00'],
        ['title' => 'Keris of Majapahit', 'genre' => 'Action', 'price' => '75000.00'],
        ['title' => 'Hantu Laut', 'genre' => 'Adventure', 'price' => '0.00'],
        ['title' => 'Kuntilanak Mansion', 'genre' => 'Horror', 'price' => '99000.00'],
        ['title' => 'Garuda Strike', 'genre' => 'Action', 'price' => '129000.00'],
        ['title' => 'Lorong Waktu', 'genre' => 'RPG', 'price' => '59000.00'],
        ['title' => 'Warung Tycoon', 'genre' => 'Simulation', 'price' => '0.00'],
        ['title' => 'Batik Heroes', 'genre' => 'Strategy', 'price' => '65000.00'],
        ['title' => 'Sawah Simulator', 'genre' => 'Simulation', 'price' => '35000.00'],
        ['title' => 'Angkot Rush', 'genre' => 'Racing', 'price' => '49000.00'],
        ['title' => 'Candi Puzzle', 'genre' => 'Puzzle', 'price' => '0.00'],
        ['title' => 'Sate Bandits', 'genre' => 'Casual', 'price' => '25000.00'],
    ];

    public function run(): void
    {
        $devs = [
            Developer::whereHas('user', fn ($q) => $q->where('email', 'dev@gplaynation.test'))->firstOrFail(),
            Developer::whereHas('user', fn ($q) => $q->where('email', 'dev2@gplaynation.test'))->firstOrFail(),
        ];

        $tags = collect(['horror-id', 'aksi', 'petualangan', 'strategi', 'simulasi', 'teka-teki', 'balapan', 'kasual', 'rpg', 'gamelokal', 'indie', 'keluarga'])
            ->map(fn ($name) => Tag::firstOrCreate(['name' => $name]));

        foreach (self::GAMES as $i => $row) {
            $slug = Str::slug($row['title']);
            $dev = $devs[$i % 2];

            $game = Game::updateOrCreate(
                ['slug' => $slug],
                [
                    'developer_id' => $dev->id,
                    'name' => $row['title'],
                    'description' => "Game lokal Indonesia: {$row['title']}. Dibuat dengan bangga oleh {$dev->studio_name}. Jelajahi dunia yang terinspirasi budaya Nusantara dengan gameplay seru dan menantang.",
                    'price' => $row['price'],
                    'genre' => $row['genre'],
                    'cover_path' => "covers/{$slug}.svg",
                    'file_path' => "installers/{$slug}.zip",
                    'status' => GameStatus::Published,
                    'rejection_feedback' => null,
                ],
            );

            $this->storeFiles($game, $slug);

            $game->screenshots()->delete();
            foreach ([1, 2] as $n) {
                $path = "screenshots/{$slug}-{$n}.svg";
                Storage::disk('public')->put($path, CoverArt::svg($row['title']." shot {$n}"));
                $game->screenshots()->create(['path' => $path, 'sort' => $n]);
            }

            $game->tags()->sync($tags->shuffle()->take(2 + ($i % 3))->pluck('id')->all());

            if (bccomp($row['price'], '0', 2) !== 0) {
                $iapCount = $i % 4; // 0–3 IAPs on paid games
                $game->iaps()->delete();
                for ($k = 0; $k < $iapCount; $k++) {
                    Iap::create([
                        'game_id' => $game->id,
                        'name' => "Paket {$row['title']} #".($k + 1),
                        'description' => 'Konten tambahan untuk memperkaya pengalaman bermain.',
                        'price' => '15000.00',
                        'type' => ['item', 'currency', 'cosmetic'][$k % 3],
                        'is_active' => true,
                    ]);
                }
            }
        }

        // One game gets an active 30% promotion.
        $promoGame = Game::where('slug', 'keris-of-majapahit')->firstOrFail();
        Promotion::updateOrCreate(
            ['game_id' => $promoGame->id],
            ['discount_percent' => 30, 'starts_at' => now()->subDay(), 'ends_at' => now()->addWeeks(2)],
        );

        // Non-published states for the moderation queues.
        $this->makeExtra(
            slug: 'naga-terakhir',
            title: 'Naga Terakhir',
            genre: 'RPG',
            price: '75000.00',
            dev: $devs[0],
            status: GameStatus::PendingReview,
        );
        $this->makeExtra(
            slug: 'pasar-malam',
            title: 'Pasar Malam',
            genre: 'Sports',
            price: '15000.00',
            dev: $devs[1],
            status: GameStatus::Rejected,
            feedback: 'Cover game mengandung aset berhak cipta. Ganti dengan aset orisinal lalu ajukan ulang.',
        );
        $this->makeExtra(
            slug: 'komodo-quest',
            title: 'Komodo Quest',
            genre: 'Adventure',
            price: '55000.00',
            dev: $devs[0],
            status: GameStatus::Archived,
        );
    }

    private function makeExtra(string $slug, string $title, string $genre, string $price, Developer $dev, GameStatus $status, ?string $feedback = null): void
    {
        $game = Game::updateOrCreate(
            ['slug' => $slug],
            [
                'developer_id' => $dev->id,
                'name' => $title,
                'description' => "Game lokal Indonesia: {$title} oleh {$dev->studio_name}.",
                'price' => $price,
                'genre' => $genre,
                'cover_path' => "covers/{$slug}.svg",
                'file_path' => "installers/{$slug}.zip",
                'status' => $status,
                'rejection_feedback' => $feedback,
            ],
        );

        $this->storeFiles($game, $slug);
    }

    private function storeFiles(Game $game, string $slug): void
    {
        Storage::disk('public')->put($game->cover_path, CoverArt::svg($game->name));
        Storage::disk('public')->put(
            $game->file_path,
            "GPlayNation installer placeholder untuk {$game->name} (slug: {$slug}).\n"
                ."Ini bukan arsip biner sungguhan — file teks ini menggantikan .zip agar unduhan demo tetap berfungsi tanpa ext-zip.\n"
                ."Terima kasih telah mendukung game lokal Indonesia!\n"
        );
    }
}
