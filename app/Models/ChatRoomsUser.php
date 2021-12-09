<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatRoomsUser extends Model
{
    use HasFactory;

    /**
     * Наименование таблицы модели
     * 
     * @var string
     */
    protected $table = "chat_rooms_user";

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
        'user_id',
        'chat_id',
    ];
}
