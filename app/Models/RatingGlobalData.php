<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingGlobalData extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'pin',
        'requests',
        'requests_moscow',
        'comings',
        'drains',
        'agreements_firsts',
        'agreements_seconds',
        'cashbox',
        'created_at',
    ];

    /**
     * Атрибуты, которые будут преобразованы
     *
     * @var array
     */
    protected $casts = [
        'requests' => "int",
        'requests_moscow' => "int",
        'comings' => "int",
        'drains' => "int",
        'agreements_firsts' => "int",
        'agreements_seconds' => "int",
        'cashbox' => "int",
    ];
}
