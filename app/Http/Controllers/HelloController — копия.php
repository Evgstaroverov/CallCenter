<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http; // Импортируем HTTP клиент
use Illuminate\Support\Facades\Config;

class HelloController extends Controller
{
    public function index()
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        
        // Делаем запрос к API Telegram

       // $response = Http::get("https://http://localhost/getUpdates.json");

	$response = Http::get("https://api.telegram.org/bot{$token}/getUpdates");

        $messages = [];
        if ($response->successful()) {
            $data = $response->json();
            // Вытаскиваем только текст сообщений
            if (isset($data['result'])) {
                $messages = $data['result'];
            }
        }

        return view('hello', ['messages' => $messages]);
    }
}

