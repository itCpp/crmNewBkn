<?php

namespace App\Models\Incomings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallsSectorQueue extends Model
{
    use HasFactory;

    /**
     * Соединение с БД, которое должна использовать модель.
     *
     * @var string
     */
    protected $connection = "incomings";

    /**
     * The attributes that should be cast
     *
     * @var array
     */
    protected $casts = [
        'data_counters' => 'array',
    ];
}
