<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatRoomsViewTime extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'chat_id',
        'user_id',
        'last_show',
    ];

    /**
     * Атрибуты, которые будут преобразованы
     *
     * @var array
     */
    protected $casts = [
        'last_show' => 'datetime'
    ];
}
