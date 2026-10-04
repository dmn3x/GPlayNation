<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SRS 3.3: DEVELOPER
        Schema::create('developers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nama_developer', 100);
            $table->string('nama_studio', 120);
            $table->text('developer_portofolio')->nullable();
            $table->timestamps();
        });

        // SRS 3.3: USER — role member/admin; developer accounts carry a developers row.
        Schema::table('users', function (Blueprint $table) {
            $table->string('pengguna_nama', 100)->after('name');
            $table->enum('role', ['user', 'developer', 'admin'])->default('user')->after('password');
            $table->enum('status', ['active', 'pending', 'rejected'])->default('active')->after('role');
            $table->string('avatar')->nullable()->after('status');
        });

        // SRS 3.3: GAME
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained()->cascadeOnDelete();
            $table->string('game_nama', 150);
            $table->text('deskripsi');
            $table->decimal('game_harga', 12, 2)->default(0);
            $table->string('game_genre', 50);
            $table->string('kategori', 50)->nullable();
            $table->string('game_status', 30)->default('draft');
            $table->string('cover')->nullable();
            $table->json('screenshots')->nullable();
            $table->string('trailer')->nullable();
            $table->string('platform')->nullable();
            $table->date('rilis')->nullable();
            $table->unsignedInteger('rating')->default(0);
            $table->unsignedBigInteger('unduhan')->default(0);
            $table->json('sysreq')->nullable();
            $table->timestamps();
        });

        // SRS 3.3: IAP
        Schema::create('iaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('iap_nama', 150);
            $table->text('deskripsi')->nullable();
            $table->decimal('iap_harga', 12, 2);
            $table->string('iap_tipe', 50);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // SRS 3.3: TRANSAKSI
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengguna_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('game_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('iap_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('transaksi_tanggal');
            $table->decimal('transaksi_total_harga', 12, 2);
            $table->string('transaksi_status', 30)->default('pending');
            $table->timestamps();
        });

        // SRS 3.3: PEMBAYARAN
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained()->cascadeOnDelete();
            $table->decimal('pembayaran_jumlah', 12, 2);
            $table->string('metode', 50)->nullable();
            $table->string('nomor_ref', 100)->nullable();
            $table->timestamps();
        });

        // SRS 3.3: PAYMENTGATEWAY
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembayaran_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway_provider', 100);
            $table->string('gateway_api_key', 255);
            $table->timestamps();
        });

        // SRS 3.3: KOMISI
        Schema::create('komisis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('komisi_persentase', 5, 2);
            $table->date('komisi_berlaku_mulai');
            $table->timestamps();
        });

        // Commission snapshot per transaction — F-08 / 3.1.4.2
        Schema::create('komisi_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained()->cascadeOnDelete();
            $table->foreignId('komisi_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('persentase', 5, 2);
            $table->decimal('nominal', 12, 2);
            $table->timestamps();
        });

        // 3.1.4.3 Kelola Payout Komisi
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained()->cascadeOnDelete();
            $table->string('periode', 50);
            $table->decimal('total_komisi', 12, 2)->default(0);
            $table->decimal('total_bersih', 12, 2)->default(0);
            $table->string('status', 30)->default('pending');
            $table->timestamp('diproses_pada')->nullable();
            $table->timestamps();

            $table->unique(['developer_id', 'periode']);
        });

        // 3.1.2.3 — a user's library (purchases + playtime)
        Schema::create('library', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengguna_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->decimal('jam_main', 8, 1)->default(0);
            $table->timestamps();

            $table->unique(['pengguna_id', 'game_id']);
        });

        // 4.2 Profile — wishlist
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengguna_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['pengguna_id', 'game_id']);
        });

        // 4.3 Community
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengguna_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('game_id')->nullable()->constrained()->nullOnDelete();
            $table->text('isi');
            $table->json('media')->nullable();
            $table->string('jenis', 20)->default('diskusi');
            $table->unsignedBigInteger('skor')->default(0);
            $table->timestamps();
        });

        Schema::create('post_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pengguna_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('naik')->default(true);
            $table->timestamps();

            $table->unique(['post_id', 'pengguna_id']);
        });

        // 3.1.5.1 / 3.1.5.2 — cached periodic rollups for reporting
        Schema::create('laporans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('komisi_id')->nullable()->constrained()->nullOnDelete();
            $table->string('laporan_periode', 50);
            $table->decimal('laporan_total_penjualan', 14, 2)->default(0);
            $table->unsignedInteger('laporan_jumlah_transaksi')->default(0);
            $table->timestamps();

            $table->unique(['developer_id', 'laporan_periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporans');
        Schema::dropIfExists('post_votes');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('library');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('komisi_transaksi');
        Schema::dropIfExists('komisis');
        Schema::dropIfExists('payment_gateways');
        Schema::dropIfExists('pembayarans');
        Schema::dropIfExists('transaksis');
        Schema::dropIfExists('iaps');
        Schema::dropIfExists('games');
        Schema::dropIfExists('developers');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pengguna_nama', 'role', 'status', 'avatar']);
        });
    }
};