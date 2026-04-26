<?php

namespace App\Services;

use DefStudio\Telegraph\Telegraph;
use App\Models\Message;
use App\Models\ChatAssignment;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected $telegraph;
    
    public function __construct()
    {
        $this->telegraph = Telegraph::bot();
    }
    
    /**
     * Получить обновления от Telegram
     */
    public function getUpdates($offset = null)
    {
        try {
            $response = $this->telegraph->getUpdates($offset);
            
            if ($response->successful()) {
                $updates = $response->json()['result'] ?? [];
                
                $lastUpdateId = 0;
                $newMessages = [];
                
                foreach ($updates as $update) {
                    $lastUpdateId = $update['update_id'];
                    
                    if (isset($update['message'])) {
                        $message = $this->processMessage($update['message']);
                        if ($message) {
                            $newMessages[] = $message;
                        }
                    }
                }
                
                return [
                    'messages' => $newMessages,
                    'last_update_id' => $lastUpdateId > 0 ? $lastUpdateId + 1 : null,
                    'has_new' => count($newMessages) > 0
                ];
            }
        } catch (\Exception $e) {
            Log::error('Telegram getUpdates error: ' . $e->getMessage());
        }
        
        return [
            'messages' => [],
            'last_update_id' => $offset,
            'has_new' => false
        ];
    }
    
    /**
     * Обработать сообщение от Telegram
     */
    protected function processMessage($message)
    {
        try {
            $chatId = $message['chat']['id'];
            $userName = $message['from']['first_name'] ?? 'Unknown';
            $text = $message['text'] ?? '';
            $date = $message['date'] ?? now()->timestamp;
            
            // Сохраняем сообщение в БД
            $savedMessage = Message::updateOrCreate(
                ['telegram_update_id' => $message['message_id']],
                [
                    'chat_id' => $chatId,
                    'user_name' => $userName,
                    'text' => $text,
                    'is_outbound' => false,
                    'telegram_date' => date('Y-m-d H:i:s', $date)
                ]
            );
            
            return [
                'id' => $savedMessage->id,
                'chat_id' => $chatId,
                'user_name' => $userName,
                'text' => $text,
                'is_outbound' => false,
                'formatted_date' => $savedMessage->created_at->format('d.m.Y H:i'),
                'relative_time' => $savedMessage->created_at->diffForHumans()
            ];
        } catch (\Exception $e) {
            Log::error('Process message error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Отправить сообщение в Telegram
     */
    public function sendMessage($chatId, $text)
    {
        try {
            $response = $this->telegraph
                ->chat($chatId)
                ->message($text)
                ->send();
            
            if ($response->successful()) {
                return true;
            }
        } catch (\Exception $e) {
            Log::error('Send message error: ' . $e->getMessage());
        }
        
        return false;
    }
}