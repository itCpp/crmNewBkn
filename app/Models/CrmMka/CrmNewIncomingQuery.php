<?php

namespace App\Models\CrmMka;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmNewIncomingQuery extends Model
{
    use HasFactory;

    /**
     * Соединение с БД, которое должна использовать модель.
     *
     * @var string
     */
    protected $connection = "mka";

    /**
     * Атрибуты, которые должны быть преобразованы
     * 
     * @var array
     */
    protected $casts = [
        'request' => 'array',
    ];
}
