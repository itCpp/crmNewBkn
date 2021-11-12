<?php

namespace App\Models\Incomings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallsSectorSetting extends Model
{
    use HasFactory;

    /**
     * Соединение с БД, которое должна использовать модель.
     *
     * @var string
     */
    protected $connection = "incomings";

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'comment',
        'count_change_queue',
        'only_queue',
        'active',
    ];
}
