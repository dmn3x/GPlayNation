<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostVote;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommunitySeeder extends Seeder
{
    private const POSTS = [
        ['body' => 'Baru saja menamatkan Pocong Runner, level pasar malamnya bikin deg-degan! Ada yang sudah dapat semua batik skin? #gamelokal #horrorid', 'tags' => ['gamelokal', 'horrorid']],
        ['body' => 'Warung Tycoon bikin nagih. Omzet warungku sudah 10 juta koin dalam seminggu. Tips upgrade gerobak dulu biar pelanggan betah. #gamelokal #indie', 'tags' => ['gamelokal', 'indie']],
        ['body' => 'Keris of Majapahit layak disebut game aksi lokal terbaik tahun ini. Combat-nya halus, musik gamelannya epik. #gamelokal', 'tags' => ['gamelokal']],
        ['body' => 'Ada yang main Candi Puzzle bareng? Level Borobudur lantai 7 susah banget, butuh petunjuk. #indie #gamelokal', 'tags' => ['indie', 'gamelokal']],
        ['body' => 'Kuntilanak Mansion + headset = pengalaman horror paling seram yang pernah kurasakan. Mainnya jangan tengah malam ya. #horrorid #gamelokal', 'tags' => ['horrorid', 'gamelokal']],
        ['body' => 'Bangga lihat game Indonesia makin berkualitas. Sudah beli 5 game lokal bulan ini, dukung terus developer kita! #gamelokal #indie', 'tags' => ['gamelokal', 'indie']],
        ['body' => 'Angkot Rush balapannya seru, rutenya mirip jalanan Bandung asli. Ngetem dulu sebelum ngebut! #gamelokal', 'tags' => ['gamelokal']],
        ['body' => 'Sawah Simulator itu healing banget. Nanem padi, panen, jual ke pasar. Cocok buat melepas penat. #indie #gamelokal', 'tags' => ['indie', 'gamelokal']],
        ['body' => 'Batik Heroes strategi perangnya dalam juga. Kombinasi motif batik buat buff pasukan itu ide jenius. #gamelokal #indie', 'tags' => ['gamelokal', 'indie']],
        ['body' => 'Hantu Laut gratis tapi kualitasnya premium. Cerita legenda nelayan panturanya menyentuh. Wajib coba! #gamelokal', 'tags' => ['gamelokal']],
        ['body' => 'Lorong Waktu plot twist-nya gila. Mesin waktu dari Candi Prambanan, siapa yang kepikiran? Penulisnya jenius. #gamelokal #indie', 'tags' => ['gamelokal', 'indie']],
        ['body' => 'Garuda Strike mode pesawat tempurnya mulus di HP kentangku. Optimisasinya patut diacungi jempol. #gamelokal', 'tags' => ['gamelokal']],
        ['body' => 'Sate Bandits cocok buat mabar sama adik. Lempar satenya satisfying banget wkwk. #gamelokal #indie', 'tags' => ['gamelokal', 'indie']],
        ['body' => 'Diskusi serius: game horror Indonesia vs luar, menurutku hantu lokal jauh lebih seram karena dekat sama budaya kita. Setuju? #horrorid #gamelokal', 'tags' => ['horrorid', 'gamelokal']],
        ['body' => 'Baru daftar jadi developer di sini. Proses verifikasinya cepat, dasbor penjualannya juga jelas. Semangat rilis game pertama! #indie #gamelokal', 'tags' => ['indie', 'gamelokal']],
    ];

    public function run(): void
    {
        $users = User::whereIn('email', [
            'user@gplaynation.test', 'user2@gplaynation.test', 'user3@gplaynation.test',
            'dev@gplaynation.test', 'dev2@gplaynation.test',
        ])->get();

        foreach (self::POSTS as $i => $row) {
            $author = $users[$i % $users->count()];

            $post = Post::create([
                'user_id' => $author->id,
                'body' => $row['body'],
                'hashtags' => $row['tags'],
                'created_at' => now()->subDays($i * 2)->subHours($i),
                'updated_at' => now()->subDays($i * 2)->subHours($i),
            ]);

            // Spread votes across the other users (deterministic, no self-votes).
            foreach ($users->where('id', '!==', $author->id)->values() as $v => $voter) {
                if (($i + $v) % 3 === 0) {
                    PostVote::create([
                        'post_id' => $post->id,
                        'user_id' => $voter->id,
                        'value' => (($i + $v) % 7 === 0) ? -1 : 1,
                    ]);
                }
            }
        }
    }
}
