<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user',
        'notif_type',
        'notification',
        'data',
        'readed_at',
        'user_by_id',
    ];

    /**
     * Атрибуты, которые преобразовываются
     *
     * @var array
     */
    protected $casts = [
        'readed_at' => 'datetime',
        'data' => 'array',
    ];

    /**
     * Создание уведомления
     * 
     * @param int $pin
     * @param string $text
     * @param string $type
     * @return \App\Models\Notification
     */
    public static function add($pin, $text, $type = null)
    {
        self::create([
            'user' => $pin,
            'notif_type' => $text,
            'notification' => $type,
        ]);
    }
}
