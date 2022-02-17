<?php

namespace App\Models\Saratov;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Personal extends Model
{
    use HasFactory;

    /**
     * Соединение с БД, которое должна использовать модель.
     *
     * @var string
     */
    protected $connection = "saratov";

    /**
     * Наименование таблицы
     * 
     * @var string
     */
    protected $table = "personal";
}
