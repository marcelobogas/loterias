<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lottery_draw_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_draw_id')->constrained()->cascadeOnDelete();
            // Denormalized on purpose: avoids a join with lottery_draws on every
            // frequency/delay/co-occurrence query, which run over thousands of rows.
            $table->foreignId('lottery_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('number');
            $table->unsignedTinyInteger('position')->nullable();
            $table->timestamps();

            $table->unique(['lottery_draw_id', 'number']);
            $table->index(['lottery_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_draw_numbers');
    }
};
