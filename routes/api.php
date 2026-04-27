<?php

use Illuminate\Support\Carbon;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HelloController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TelegramController;

//use App\Models\TelegraphBot;

use DefStudio\Telegraph\Models\TelegraphBot;

use App\Models\Message;


// Публичные маршруты
Route::post('/login', [AuthController::class, 'apiLogin']);
Route::post('/register', [AuthController::class, 'apiRegister']);


Route::get('/tg-check', function () {

            $token = config('services.telegram.bot_token');

            $bot = TelegraphBot::where('token', $token)->first();
            $updates = $bot->updates()->toArray();

            foreach ($updates as $update) {
                if (isset($update['message'])) {
                    Message::updateOrCreate(
                        ['telegram_update_id' => $update['id']],
                        [
                            'chat_id' => $update['message']['chat']['id'],
                            'user_name' => $update['message']['from']['first_name'] ?? 'Anon',
                            'text' => $update['message']['text'] ?? '',
                            'is_outbound' => false,
                            'telegram_date' => Carbon::parse(Carbon::parse($update['message']['date'])->toDateTimeString())->timestamp ?? null
                        ]
                    );
                }
            }





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