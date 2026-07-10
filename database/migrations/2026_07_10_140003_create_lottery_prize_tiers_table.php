<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lottery_prize_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('hits');
            $table->string('label', 50)->nullable();
            $table->timestamps();

            $table->unique(['lottery_id', 'hits']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_prize_tiers');
    }
};
