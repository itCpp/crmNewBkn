<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatMessage extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'chat_id',
        'type',
        'message',
        'body',
    ];

    /**
     * Атрибуты, которые должны быть преобразованы
     * 
     * @var array
     */
    protected $casts = [
        'body' => 'array',
    ];
}
