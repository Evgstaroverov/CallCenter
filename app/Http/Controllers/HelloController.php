<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\ChatAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Facades\Telegraph;

use Illuminate\Support\Carbon;

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
	return true;


        } catch (\Exception $e) {
            // Логируем ошибку если нужно
            return false;
        }
    }


    // Получить список чатов (с учетом назначений)
    public function getChats()
    {
        $user = Auth::user();
        
        // ✅ ВСЕГДА загружаем свежие сообщения из Telegram API
        $this->fetchUpdatesFromTelegram();
        
        // Базовый запрос для получения чатов
        $query = DB::table('messages')
            ->select(
                'chat_id',
                DB::raw("MAX(CASE WHEN is_outbound = 0 THEN user_name END) as user_name"),
                DB::raw('MAX(created_at) as last_message_at'), 
                DB::raw('COUNT(*) as message_count')
            )
            ->groupBy('chat_id');
        
        // Получаем все чаты
        $allChats = $query->orderBy('last_message_at', 'desc')->get();
        
        // Получаем назначенные чаты
        $assignments = ChatAssignment::pluck('user_id', 'chat_id');
        
        // Фильтруем чаты
        $chats = $allChats->filter(function ($chat) use ($user, $assignments) {
            $assignedUserId = $assignments->get($chat->chat_id);
            
            // Показываем чат если:
            // 1. Чат не назначен никому (свободен)
            // 2. Чат назначен текущему пользователю
            // 3. Пользователь - администратор (если есть такая роль)
            return !$assignedUserId || 
                   $assignedUserId == $user->id || 
                   $user->role === 'admin';
        })->values();
        
        // Добавляем информацию о назначении
        $chats = $chats->map(function ($chat) use ($assignments, $user) {
            $assignedUserId = $assignments->get($chat->chat_id);
            
            // ✅ Сохраняем оригинальную дату для фронтенда
            $chat->last_message_time = $chat->last_message_at;
            
            // ✅ Добавляем человекочитаемое время
            $chat->last_message_human = \Carbon\Carbon::parse($chat->last_message_at)->diffForHumans();
            
            $lastMessage = Message::where('chat_id', $chat->chat_id)
                ->orderBy('created_at', 'desc')
                ->first();
            
            $lastText = $lastMessage ? $lastMessage->text : 'Нет сообщений';
            $chat->last_message_preview = mb_strlen($lastText) > 50 
                ? mb_substr($lastText, 0, 50) . '...' 
                : $lastText;
            
            // Информация о назначении
            $chat->is_assigned = !!$assignedUserId;
            $chat->assigned_to = $assignedUserId ? (int)$assignedUserId : null;
            $chat->is_mine = $assignedUserId == $user->id;
            
            // Кто назначен (имя пользователя)
            if ($assignedUserId) {
                $assignedUser = \App\Models\User::find($assignedUserId);
                $chat->assigned_user_name = $assignedUser ? $assignedUser->name : 'Неизвестный';
            }
            
            return $chat;
        });

        return response()->json([
            'chats' => $chats,
            'can_assign' => true,
            'updated_at' => now()->toDateTimeString() // Для отладки
        ]);
    }

    // Получить сообщения конкретного чата (с проверкой доступа)
    public function getMessages($chatId)
    {
        $user = Auth::user();
        
        // Проверяем, назначен ли чат кому-то
        $assignment = ChatAssignment::where('chat_id', $chatId)->first();
        
        // Если чат назначен и не текущему пользователю - запрещаем доступ
        if ($assignment && $assignment->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'error' => 'Этот чат уже взят в работу другим пользователем',
                'messages' => []
            ], 403);
        }
        
        // ✅ ВСЕГДА загружаем свежие сообщения из Telegram API
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
            'is_assigned' => !!$assignment,
            'assigned_to' => $assignment ? $assignment->user_id : null,
            'is_mine' => $assignment ? $assignment->user_id === $user->id : false
        ]);
    }

    // Назначить чат пользователю (взять в работу)
    public function assignChat($chatId)
    {
        $user = Auth::user();
        
        // Проверяем, не назначен ли уже чат
        $existingAssignment = ChatAssignment::where('chat_id', $chatId)->first();
        
        if ($existingAssignment) {
            if ($existingAssignment->user_id === $user->id) {
                // Чат уже у этого пользователя
                return response()->json([
                    'message' => 'Этот чат уже у вас в работе',
                    'assigned' => true
                ]);
            } else {
                // Чат у другого пользователя
                return response()->json([
                    'message' => 'Этот чат уже взят в работу другим пользователем',
                    'assigned' => false
                ], 403);
            }
        }
        
        // Назначаем чат
        $assignment = ChatAssignment::create([
            'chat_id' => $chatId,
            'user_id' => $user->id,
            'user_name' => $user->name,
        ]);
        
        return response()->json([
            'message' => 'Чат взят в работу',
            'assignment' => $assignment,
            'assigned' => true
        ]);
    }
    
    // Снять назначение (освободить чат)
    public function releaseChat($chatId)
    {
        $user = Auth::user();
        
        $assignment = ChatAssignment::where('chat_id', $chatId)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$assignment && $user->role !== 'admin') {
            return response()->json([
                'message' => 'Вы не можете снять этот чат с работы',
            ], 403);
        }
        
        // Админ может снять любой чат
        if ($user->role === 'admin') {
            ChatAssignment::where('chat_id', $chatId)->delete();
        } else {
            $assignment->delete();
        }
        
        return response()->json([
            'message' => 'Чат освобожден',
            'assigned' => false
        ]);
    }

    // Отправить сообщение (с проверкой прав)
    public function store(Request $request)
    {
        $request->validate([
            'chat_id' => 'required',
            'text' => 'required'
        ]);
        
        $user = Auth::user();
        $chatId = $request->chat_id;
        
        // Проверяем, назначен ли чат
        $assignment = ChatAssignment::where('chat_id', $chatId)->first();
        
        // Если чат назначен и не текущему пользователю - запрещаем отправку
        if ($assignment && $assignment->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'error' => 'Вы не можете отправлять сообщения в этот чат'
            ], 403);
        }
        
        // Если чат не назначен - автоматически назначаем текущему пользователю
        if (!$assignment) {
            ChatAssignment::create([
                'chat_id' => $chatId,
                'user_id' => $user->id,
                'user_name' => $user->name,
            ]);
        }

        
        $localMsg = Message::create([
            'chat_id' => $chatId,
            'text' => $request->text,
            'user_name' => $user->name . ' (Бот)',
            'is_outbound' => true
        ]);

        try {


	Telegraph::chat($request->chat_id)
  	  ->message($request->text)
  	  ->send();


        } catch (\Exception $e) {
            // Оставляем в базе
        }

        $localMsg->formatted_date = $localMsg->created_at->format('d.m.Y H:i');
        $localMsg->relative_time = $localMsg->created_at->diffForHumans();

        return response()->json($localMsg);
    }
}