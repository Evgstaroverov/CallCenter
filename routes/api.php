<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HelloController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TelegramController;

// Публичные маршруты
Route::post('/login', [AuthController::class, 'apiLogin']);
Route::post('/register', [AuthController::class, 'apiRegister']);


Route::get('/tg-check', function () {
    $token = config('services.telegram.bot_token');
    $response = Http::timeout(3)->get("https://api.telegram.org/bot{$token}/getUpdates");
    
    if ($response->successful()) {
        $updates = $response->json()['result'] ?? [];
        dd($updates); // Выведет данные на экран и остановит работу
    }

    return "Ошибка запроса к Telegram";
});


// Защищенные маршруты
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'apiLogout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Чаты
    Route::get('/chats', [HelloController::class, 'getChats']);
    Route::get('/messages/{chatId}', [HelloController::class, 'getMessages']);
    Route::post('/send-message', [HelloController::class, 'store']);
    
    // Назначение чатов
    Route::post('/chats/{chatId}/assign', [HelloController::class, 'assignChat']);
    Route::post('/chats/{chatId}/release', [HelloController::class, 'releaseChat']);
});