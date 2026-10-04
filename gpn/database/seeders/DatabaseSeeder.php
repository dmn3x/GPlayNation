<?php

namespace Database\Seeders;

use App\Models\Developer;
use App\Models\Game;
use App\Models\Komisi;
use App\Models\KomisiTransaksi;
use App\Models\Library;
use App\Models\Pembayaran;
use App\Models\Post;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin GPlayNation',
            'pengguna_nama' => 'Admin',
            'email' => 'admin@gplaynation.test',
        ]);

        $devUser = User::factory()->create([
            'name' => 'Nusantara Studios',
            'pengguna_nama' => 'Nusantara Studios',
            'email' => 'dev@gplaynation.test',
            'role' => 'developer',
        ]);

        $developer = $devUser->developer()->create([
            'nama_developer' => 'Nusantara Studios',
            'nama_studio' => 'Nusantara Studios',
            'developer_portofolio' => 'Studio game lokal yang berfokus pada action RPG bertema Nusantara.',
        ]);

        // A developer still awaiting verification, so the admin queue has content.
        $pending = User::factory()->developer()->create([
            'name' => 'Barabai Forge',
            'pengguna_nama' => 'Barabai Forge',
            'email' => 'pending@gplaynation.test',
            'status' => 'pending',
        ]);

        $pending->developer()->create([
            'nama_developer' => 'Barabai Forge',
            'nama_studio' => 'Barabai Forge',
        ]);

        $pengguna = User::factory()->create([
            'name' => 'Muftahh',
            'pengguna_nama' => 'Muftahh',
            'email' => 'user@gplaynation.test',
        ]);

        // Games mirror the Figma Store grid (frame 6:1698).
        // Columns are: name, price, category, genre+genre pair, studio.
        $catalog = [
            ["Ananta's Quest", 230000, 'Action RPG', ['RPG', 'Adventure'], 'Nusantara Studios'],
            ['Pixel Rat', 150000, 'Platformer', ['Platformer', 'Action'], 'Pixel Forge'],
            ['Pixel Rat 2', 150000, 'Platformer', ['Platformer', 'Action'], 'Pixel Forge'],
            ['Neon Nights', 300000, 'Cyberpunk', ['Cyberpunk', 'RPG'], 'Neon Byte'],
            ['Shadow Ninja', 180000, 'Stealth', ['Stealth', 'Indie'], 'Kage Studio'],
            ['Shadow Ninja Remaster', 180000, 'Stealth', ['Stealth', 'Indie'], 'Kage Studio'],
            ['Space Explorer', 350000, 'Sci-Fi', ['Sci-Fi', 'Simulation'], 'Orbit Nine'],
            ['Farm Tycoon', 210000, 'Management', ['Management', 'Casual'], 'Sawah Digital'],
            ['Farm Tycoon Deluxe', 210000, 'Management', ['Management', 'Casual'], 'Sawah Digital'],
            ['Cosmic Drift', 275000, 'Racing', ['Racing', 'Arcade'], 'Nebula Pilot'],
        ];

        $cover = fn (string $seed) => 'https://picsum.photos/seed/'.urlencode($seed).'/960/540';

        $games = collect($catalog)->map(function (array $row, int $i) use ($developer, $cover) {
            [$nama, $harga, $kategori, $genres, $studio] = $row;

            return $developer->games()->create([
                'game_nama' => $nama,
                'deskripsi' => "{$nama} adalah game karya {$studio} yang membawa nuansa Nusantara ke layar Anda. "
                    ."Nikmati narasi mendalam, combat yang responsif, dan dunia yang hidup dari sebuah game lokal pilihan.",
                'game_harga' => $harga,
                'game_genre' => $genres[1],
                'kategori' => $kategori,
                'game_status' => 'published',
                'cover' => $cover($nama),
                'screenshots' => [$cover($nama.'-1'), $cover($nama.'-2'), $cover($nama.'-3')],
                'platform' => 'Windows, Mac',
                'rilis' => Carbon::now()->subDays(random_int(3, 90)),
                'rating' => random_int(38, 50),
                'unduhan' => random_int(1200, 140000),
                'sysreq' => [
                    'os' => 'Windows 10/11 64-bit',
                    'processor' => 'Intel Core i5-8400 / AMD Ryzen 5 2600',
                    'memory' => '16 GB RAM',
                    'graphics' => 'NVIDIA GeForce GTX 1060 / AMD Radeon RX 580',
                    'storage' => '45 GB available space',
                ],
            ]);
        });

        // IAP catalogue (SRS F-05, F-07)
        $iaps = [
            ['Skins Pack', 'Cosmetic skins untuk karakter utama.', 35000, 'Skin'],
            ['Expansion: Deep Archipelago', 'Ekspansi cerita dan area baru.', 60000, 'Expansion'],
            ['Founder Pack', 'Bundling aset eksklusif dan bonus.', 120000, 'Bundle'],
        ];

        foreach ($games->take(3) as $game) {
            foreach ($iaps as [$nama, $desc, $harga, $tipe]) {
                $game->iaps()->create([
                    'iap_nama' => $nama,
                    'deskripsi' => $desc,
                    'iap_harga' => $harga,
                    'iap_tipe' => $tipe,
                ]);
            }
        }

        // One game awaiting moderation so the admin queue is non-empty.
        $developer->games()->create([
            'game_nama' => 'Ananta\'s Quest II',
            'deskripsi' => 'Sekuel Ananta\'s Quest dengan dunia archipelago yang lebih luas.',
            'game_harga' => 320000,
            'game_genre' => 'RPG',
            'kategori' => 'Action RPG',
            'game_status' => 'pending_review',
            'cover' => $cover('aq2'),
        ]);

        // Commission schedule (F-08)
        $komisi = Komisi::create([
            'admin_id' => $admin->id,
            'komisi_persentase' => 20,
            'komisi_berlaku_mulai' => now()->startOfYear(),
        ]);

        // Settled transactions so reporting has numbers (F-09, F-10)
        $library = [];
        foreach ($games->take(6) as $game) {
            for ($k = 0; $k < random_int(2, 9); $k++) {
                $tanggal = Carbon::now()->subDays(random_int(0, 150));
                $transaksi = Transaksi::create([
                    'pengguna_id' => $pengguna->id,
                    'game_id' => $game->id,
                    'transaksi_tanggal' => $tanggal,
                    'transaksi_total_harga' => $game->game_harga,
                    'transaksi_status' => 'settlement',
                ]);

                Pembayaran::create([
                    'transaksi_id' => $transaksi->id,
                    'pembayaran_jumlah' => $game->game_harga,
                    'metode' => collect(['gopay', 'ovo', 'dana', 'va_bca', 'credit_card'])->random(),
                    'nomor_ref' => 'trx-'.$transaksi->id,
                ]);

                KomisiTransaksi::create([
                    'transaksi_id' => $transaksi->id,
                    'komisi_id' => $komisi->id,
                    'persentase' => 20,
                    'nominal' => $game->game_harga * 0.20,
                ]);

                $library[$game->id] = true;
            }
        }

        // SRS 4.2 — the Profile screen's My Library grid
        foreach (array_keys($library) as $gameId) {
            Library::create([
                'pengguna_id' => $pengguna->id,
                'game_id' => $gameId,
                'jam_main' => random_int(0, 120),
            ]);
        }

        // SRS 4.3 — community feed
        $seedPosts = [
            ["Finally beat the Shadow King after 47 attempts — here's the build that worked", $games[0]->id, 128],
            ['Ada yang udah coba update Neon Nights 2.0?_patch sequelnya_.', $games[3]->id, 87],
            ['Anggota baru di sini. Recommends game lokal apa buat weekend?', $games[6]->id, 45],
            ['Space Explorer screenshot dari alpha 3 — lighting renderer-nya bagus banget.', $games[6]->id, 39],
        ];

        foreach ($seedPosts as [$isi, $gameId, $skor]) {
            $post = Post::create([
                'pengguna_id' => $pengguna->id,
                'game_id' => $gameId,
                'isi' => $isi,
                'jenis' => 'Guide',
                'skor' => $skor,
            ]);

            // Trending sidebar needs hashtag-bearing posts.
            $post->votes()->create(['pengguna_id' => $devUser->id, 'naik' => true]);
        }

        DB::table('wishlists')->insert([
            ['pengguna_id' => $pengguna->id, 'game_id' => $games[6]->id, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}