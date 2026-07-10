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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lottery_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('numbers_chosen');
            $table->decimal('price', 10, 2);
            $table->string('strategy', 40)->nullable();
            $table->uuid('generation_batch_id')->nullable();
            $table->string('label', 100)->nullable();
            $table->unsignedInteger('for_contest_number')->nullable();
            $table->dateTime('checked_at')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'lottery_id']);
            $table->index('generation_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
