<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rtp_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->decimal('old_rtp', 5, 2);
            $table->decimal('new_rtp', 5, 2);
            $table->decimal('old_daily_rtp', 5, 2)->nullable();
            $table->decimal('new_daily_rtp', 5, 2)->nullable();
            $table->decimal('change_percentage', 5, 2);
            $table->timestamp('detected_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rtp_changes');
    }
};
