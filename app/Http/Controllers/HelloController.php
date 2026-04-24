<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class HelloController extends Controller
{
    // Показать главную страницу
    public function index()
    {
        return view('app');
    }

    // Загрузка сообщений из Telegram API
    private function fetchUpdatesFromTelegram()
    {
        try {
            $response = Http::timeout(3)->get("http://localhost/getUpdates1.json");
            
            if ($response->successful()) {
                $updates = $response->json()['result'] ?? [];
                
                foreach ($updates as $update) {
                    if (isset($update['message'])) {
                        Message::updateOrCreate(
                            ['telegram_update_id' => $update['update_id']],
                            [
                                'chat_id' => $update['message']['chat']['id'],
                                'user_name' => $update['message']['from']['first_name'] ?? 'Anon',
                                'text' => $update['message']['text'] ?? '',
                                'is_outbound' => false,
                                'telegram_date' => $update['message']['date'] ?? null
                            ]
                        );
                    }
                }
                
                return true;
            }
        } catch (\Exception $e) {
            // Логируем ошибку если нужно
        }
        
        return false;
    }

    // Получить список чатов
    public function getChats()
    {
        // Проверяем, есть ли сообщения в базе
        $messageCount = DB::table('messages')->count();
        
        // Если сообщений нет, загружаем из API
        if ($messageCount == 0) {
            $this->fetchUpdatesFromTelegram();
        }
        
        // Получаем чаты
        $chats = DB::table('messages')
            ->select(
                'chat_id',
                DB::raw("MAX(CASE WHEN is_outbound = 0 THEN user_name END) as user_name"),
                DB::raw('MAX(created_at) as last_message_at'), 
                DB::raw('COUNT(*) as message_count')
            )
            ->groupBy('chat_id')
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(function ($chat) {
                $chat->last_message_time = \Carbon\Carbon::parse($chat->last_message_at)->diffForHumans();
                
                $lastMessage = \App\Models\Message::where('chat_id', $chat->chat_id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                $lastText = $lastMessage ? $lastMessage->text : 'Нет сообщений';
                $chat->last_message_preview = mb_strlen($lastText) > 50 
                    ? mb_substr($lastText, 0, 50) . '...' 
                    : $lastText;
                
                return $chat;
            });

        return response()->json([
            'chats' => $chats
        ]);
    }

    // Получить сообщения конкретного чата
    public function getMessages($chatId)
    {
        // Загружаем свежие сообщения при каждом открытии чата
        $this->fetchUpdatesFromTelegram();

        $messages = Message::where('chat_id', $chatId)
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get()
            ->map(function ($message) {
                $message->formatted_date = $message->created_at->format('d.m.Y H:i');
                $message->relative_time = $message->created_at->diffForHumans();
                return $message;
            });

        return response()->json([
            'messages' => $messages,
            'is_local' => false
        ]);
    }

    // Отправить сообщение
    public function store(Request $request)
    {
        $request->validate([
            'chat_id' => 'required',
            'text' => 'required'
        ]);

        $token = env('TELEGRAM_BOT_TOKEN');
        
        $localMsg = Message::create([
            'chat_id' => $request->chat_id,
            'text' => $request->text,
            'user_name' => 'Я (Бот)',
            'is_outbound' => true
        ]);

        try {
            // Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            //     'chat_id' => $request->chat_id,
            //     'text' => $request->text,
            // ]);
        } catch (\Exception $e) {
            // Оставляем в базе
        }

        $localMsg->formatted_date = $localMsg->created_at->format('d.m.Y H:i');
        $localMsg->relative_time = $localMsg->created_at->diffForHumans();

        return response()->json($localMsg);
    }
}