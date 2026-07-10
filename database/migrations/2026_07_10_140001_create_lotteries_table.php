<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotteries', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 30)->unique();
            $table->string('name', 60);
            $table->string('caixa_api_slug', 30)->nullable();
            $table->unsignedSmallInteger('universe_size');
            $table->unsignedTinyInteger('numbers_drawn');
            $table->unsignedTinyInteger('min_numbers_per_game');
            $table->unsignedTinyInteger('max_numbers_per_game');
            $table->json('draw_days_of_week')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('color_hex', 7)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotteries');
    }
};
