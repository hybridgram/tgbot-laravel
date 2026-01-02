<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_updates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('update_id')->unique();
            $table->string('bot_id');
            $table->json('update_data');
            $table->enum('mode', ['polling', 'webhook']);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['bot_id', 'mode']);
            $table->index('processed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_updates');
    }
};
