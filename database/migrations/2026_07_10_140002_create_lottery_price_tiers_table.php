<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lottery_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('numbers_chosen');
            $table->unsignedInteger('combinations_count');
            $table->decimal('price', 10, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->unique(['lottery_id', 'numbers_chosen', 'effective_from'], 'price_tiers_lottery_numbers_effective_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_price_tiers');
    }
};
