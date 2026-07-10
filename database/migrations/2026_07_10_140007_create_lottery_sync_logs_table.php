<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lottery_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['api', 'csv']);
            $table->enum('status', ['success', 'partial', 'failed']);
            $table->unsignedInteger('contests_synced')->default(0);
            $table->text('message')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_sync_logs');
    }
};
