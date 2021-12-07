<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatIdUser extends Model
{
    use HasFactory;

    /**
     * Наименование таблицы модели
     * 
     * @var string
     */
    protected $table = "chat_id_user";
}
