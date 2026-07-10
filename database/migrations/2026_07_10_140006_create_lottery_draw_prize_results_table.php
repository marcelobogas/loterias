<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lottery_draw_prize_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_draw_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lottery_prize_tier_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('winners_count')->default(0);
            $table->decimal('prize_amount', 14, 2)->nullable();
            $table->timestamps();

            $table->unique(['lottery_draw_id', 'lottery_prize_tier_id'], 'draw_prize_tier_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_draw_prize_results');
    }
};
