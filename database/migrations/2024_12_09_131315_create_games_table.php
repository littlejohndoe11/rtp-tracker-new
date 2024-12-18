<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('provider_id')->constrained()->onDelete('cascade');
            $table->string('category');
            $table->string('image')->nullable();
            $table->decimal('theoretical_rtp', 5, 2)->default(0);
            $table->decimal('current_rtp', 5, 2)->default(0);
            $table->decimal('daily_rtp', 5, 2)->default(0);
            $table->decimal('weekly_rtp', 5, 2)->default(0);
            $table->decimal('monthly_rtp', 5, 2)->default(0);
            $table->decimal('hit_ratio', 5, 2)->default(0);
            $table->string('risk_level')->nullable();
            $table->integer('paylines')->nullable();
            $table->decimal('min_bet', 10, 2)->nullable();
            $table->decimal('max_bet', 10, 2)->nullable();
            $table->boolean('is_trending')->default(false);
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_hot')->default(false);
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
