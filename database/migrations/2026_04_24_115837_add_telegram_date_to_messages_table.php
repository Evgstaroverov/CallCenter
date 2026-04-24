<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Проверяем, существует ли колонка перед добавлением
        if (!Schema::hasColumn('messages', 'telegram_date')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->unsignedBigInteger('telegram_date')->nullable()->after('text');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('messages', 'telegram_date')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropColumn('telegram_date');
            });
        }
    }
};