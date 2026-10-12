<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('developers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('studio_name', 120);
            $table->text('portfolio')->nullable();
            $table->string('payout_bank', 100)->nullable();
            $table->string('payout_account', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 12, 2)->default(0);
            $table->string('genre', 50);
            $table->string('cover_path');
            $table->string('file_path');
            $table->string('trailer_url')->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('rejection_feedback')->nullable();
            $table->timestamps();
            $table->index(['status', 'genre']);
        });

        Schema::create('game_screenshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->timestamps();
        });

        Schema::create('game_tag', function (Blueprint $table) {
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['game_id', 'tag_id']);
        });

        Schema::create('iaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('type', 20)->default('item');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('discount_percent');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamps();
        });

        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'game_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('iaps');
        Schema::dropIfExists('game_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('game_screenshots');
        Schema::dropIfExists('games');
        Schema::dropIfExists('developers');
    }
};
