<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotifications extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'user_pin',
        'message',
        'data',
        'readed_at',
    ];

    /**
     * Атрибуты, которые преобразовываются
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
    ];
}
