<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Создаем таблицу для назначения чатов
        if (!Schema::hasTable('chat_assignments')) {
            Schema::create('chat_assignments', function (Blueprint $table) {
                $table->id();
                $table->string('chat_id');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('user_name')->nullable();
                $table->timestamps();
                
                // Один чат может быть назначен только одному пользователю
                $table->unique('chat_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_assignments');
    }
};
