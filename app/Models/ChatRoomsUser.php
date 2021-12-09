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
}
