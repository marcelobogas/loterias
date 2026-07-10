<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lottery_draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('contest_number');
            $table->date('draw_date');
            $table->boolean('accumulated')->default(false);
            $table->decimal('collection_amount', 14, 2)->nullable();
            $table->decimal('accumulated_amount', 14, 2)->nullable();
            $table->decimal('estimated_next_prize', 14, 2)->nullable();
            $table->unsignedInteger('next_contest_number')->nullable();
            $table->date('next_draw_date')->nullable();
            $table->string('location', 150)->nullable();
            $table->enum('source', ['api', 'csv', 'manual'])->default('api');
            $table->json('raw_payload')->nullable();
            $table->dateTime('imported_at')->nullable();
            $table->timestamps();

            $table->unique(['lottery_id', 'contest_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_draws');
    }
};
