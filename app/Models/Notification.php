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
        'user_id',
        'notif_type',
        'notification',
        'readed_at',
    ];

    /**
     * Атрибуты, которые преобразовываются в json
     *
     * @var array
     */
    protected $casts = [
        'readed_at' => 'datetime',
    ];

    /**
     * Создание уведомления
     * 
     * @param int $user_id
     * @param string $text
     * @param string $type
     * @return \App\Models\Notification
     */
    public static function add($user_id, $text, $type = null)
    {
        self::create([
            'user_id' => $user_id,
            'notif_type' => $text,
            'notification' => $type,
        ]);
    }
}
