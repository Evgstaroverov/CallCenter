<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'telegram_update_id',
        'chat_id',
        'user_name',
        'text',
        'telegram_date',
        'is_outbound',
    ];
}