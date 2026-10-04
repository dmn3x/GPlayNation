<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 30)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('iap_id')->nullable()->constrained('iaps')->nullOnDelete();
            $table->string('type', 10);
            $table->decimal('total', 12, 2);
            $table->string('status', 10)->default('pending');
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['game_id', 'status']);
        });

        Schema::create('pembayarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksis')->cascadeOnDelete();
            $table->string('method', 20);
            $table->decimal('amount', 12, 2);
            $table->string('reference_no', 100);
            $table->json('payload');
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('komisis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('percentage', 5, 2);
            $table->date('effective_from');
            $table->timestamps();
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('period', 7);
            $table->decimal('gross', 14, 2);
            $table->decimal('commission', 14, 2);
            $table->decimal('net', 14, 2);
            $table->string('status', 10)->default('processed');
            $table->timestamps();
        });

        // komisi_transaksi AFTER payouts (payout_id FK), libraries/iap_ownerships here (transaksis FK)
        Schema::create('komisi_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->unique()->constrained('transaksis')->cascadeOnDelete();
            $table->foreignId('komisi_id')->constrained('komisis')->cascadeOnDelete();
            $table->decimal('percentage', 5, 2);
            $table->decimal('amount', 12, 2);
            $table->decimal('developer_amount', 12, 2);
            $table->foreignId('payout_id')->nullable()->constrained('payouts')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('libraries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaksi_id')->nullable()->constrained('transaksis')->nullOnDelete();
            $table->string('source', 10)->default('purchase');
            $table->boolean('is_showcased')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'game_id']);
        });

        Schema::create('iap_ownerships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('iap_id')->constrained('iaps')->cascadeOnDelete();
            $table->foreignId('transaksi_id')->constrained('transaksis')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'iap_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iap_ownerships');
        Schema::dropIfExists('libraries');
        Schema::dropIfExists('komisi_transaksi');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('komisis');
        Schema::dropIfExists('pembayarans');
        Schema::dropIfExists('transaksis');
    }
};
