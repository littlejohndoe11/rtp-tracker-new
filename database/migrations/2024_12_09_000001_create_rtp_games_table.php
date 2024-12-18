<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rtp_games', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider');
            $table->decimal('rtp', 5, 2);
            $table->string('category');
            $table->timestamp('last_updated');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rtp_games');
    }
};
