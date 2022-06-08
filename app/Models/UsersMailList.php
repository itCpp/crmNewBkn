<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersMailList extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'icon',
        'type',
        'message',
        'to_push',
        'to_notice',
        'to_online',
        'to_telegram',
        'markdown',
        'author_pin',
        'done_at',
    ];

    /**
     * Атрибуты, которые будут преобразованы
     *
     * @var array
     */
    protected $casts = [
        'to_push' => "boolean",
        'to_notice' => "boolean",
        'to_online' => "boolean",
        'to_telegram' => "boolean",
        'markdown' => "boolean",
        'response' => "array",
        'done_at' => "datetime",
    ];
}
