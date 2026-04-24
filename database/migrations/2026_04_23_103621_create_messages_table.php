<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('messages', function (Blueprint $table) {
        $table->id();
        $table->bigInteger('telegram_update_id')->unique()->nullable(); // Чтобы не дублировать
        $table->bigInteger('chat_id');
        $table->string('user_name')->nullable();
        $table->text('text');
        $table->boolean('is_outbound')->default(false); // Исходящее или входящее
        $table->timestamps();
	$table->unsignedBigInteger('telegram_date')->nullable(); // Unix timestamp из Telegram
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
