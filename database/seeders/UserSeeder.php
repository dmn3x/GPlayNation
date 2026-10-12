<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Developer;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@gplaynation.test'],
            ['name' => 'GPlayNation Admin', 'password' => 'password', 'role' => Role::Admin, 'status' => UserStatus::Active],
        );

        $dev = User::updateOrCreate(
            ['email' => 'dev@gplaynation.test'],
            ['name' => 'Budi Santoso', 'password' => 'password', 'role' => Role::Developer, 'status' => UserStatus::Active],
        );
        Developer::updateOrCreate(
            ['user_id' => $dev->id],
            ['studio_name' => 'Studio Nusantara', 'portfolio' => 'Studio game lokal dari Yogyakarta. Kami membuat game aksi dan horor berlatar budaya Indonesia.'],
        );

        $dev2 = User::updateOrCreate(
            ['email' => 'dev2@gplaynation.test'],
            ['name' => 'Sinta Maharani', 'password' => 'password', 'role' => Role::Developer, 'status' => UserStatus::Active],
        );
        Developer::updateOrCreate(
            ['user_id' => $dev2->id],
            ['studio_name' => 'Arus Digital', 'portfolio' => 'Studio indie dari Bandung. Fokus pada simulasi, tycoon, dan puzzle kasual.'],
        );

        $pending = User::updateOrCreate(
            ['email' => 'pending@gplaynation.test'],
            ['name' => 'Rizky Pratama', 'password' => 'password', 'role' => Role::Developer, 'status' => UserStatus::Pending],
        );
        Developer::updateOrCreate(
            ['user_id' => $pending->id],
            ['studio_name' => 'Lentera Studio', 'portfolio' => 'Studio baru menunggu verifikasi admin.'],
        );

        $rejected = User::updateOrCreate(
            ['email' => 'rejected@gplaynation.test'],
            ['name' => 'Ditolak Dev', 'password' => 'password', 'role' => Role::Developer, 'status' => UserStatus::Rejected, 'rejection_reason' => 'Dokumen studio tidak valid. Silakan lengkapi portofolio dan ajukan ulang.'],
        );
        Developer::updateOrCreate(
            ['user_id' => $rejected->id],
            ['studio_name' => 'Studio Bodong', 'portfolio' => 'Pengajuan ditolak karena dokumen tidak lengkap.'],
        );

        foreach ([
            ['user@gplaynation.test', 'Andi Wijaya'],
            ['user2@gplaynation.test', 'Dewi Lestari'],
            ['user3@gplaynation.test', 'Agus Setiawan'],
        ] as [$email, $name]) {
            User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => 'password', 'role' => Role::User, 'status' => UserStatus::Active],
            );
        }

        unset($admin);
    }
}
