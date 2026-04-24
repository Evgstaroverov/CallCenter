<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http; // Импортируем HTTP клиент
use Illuminate\Support\Facades\Config;

use Illuminate\Http\Request; // Не забудь импортировать в начале файла

use App\Models\Message;

class HelloController extends Controller
{


public function index()
{
    $token = env('TELEGRAM_BOT_TOKEN');
    $isLocal = false;

    try {
        $response = Http::timeout(3)->get("http://localhost/getUpdates3.json");
        
        if ($response->successful()) {
            $updates = $response->json()['result'] ?? [];
            foreach ($updates as $update) {
                // Сохраняем только те, которых еще нет (по update_id)
                Message::updateOrCreate(
                    ['telegram_update_id' => $update['update_id']],
                    [
                        'chat_id' => $update['message']['chat']['id'],
                        'user_name' => $update['message']['from']['first_name'] ?? 'Anon',
                        'text' => $update['message']['text'] ?? '',
                        'is_outbound' => false
                    ]
                );
            }
        } else {
            $isLocal = true; // Telegram ответил ошибкой
        }
    } catch (\Exception $e) {
        $isLocal = true; // Telegram недоступен (тайм-аут)
    }

    // Берем последние 50 сообщений из базы
    $messages = Message::orderBy('created_at', 'desc')->take(50)->get();

    return response()->json([
        'messages' => $messages,
        'is_local' => $isLocal
    ]);
}

public function store(Request $request)
{
    $token = env('TELEGRAM_BOT_TOKEN');
    
    // Сначала сохраняем в свою базу как исходящее
    $localMsg = Message::create([
        'chat_id' => $request->chat_id,
        'text' => $request->text,
        'user_name' => 'Я (Бот)',
        'is_outbound' => true
    ]);

    try {
       // Http::post("https://telegram.org{$token}/sendMessage", [
        //   'chat_id' => $request->chat_id,
           //'text' => $request->text,
     //   ]);
    } catch (\Exception $e) {
        // Оставляем в базе, но помечаем ошибку или просто игнорим (оно уже там)
    }

    return response()->json($localMsg);
}



}

        