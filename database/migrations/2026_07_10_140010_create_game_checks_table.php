<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lottery_draw_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('hits');
            $table->decimal('prize_amount', 14, 2)->default(0);
            $table->foreignId('lottery_prize_tier_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('checked_at');
            $table->timestamps();

            $table->unique(['game_id', 'lottery_draw_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_checks');
    }
};
