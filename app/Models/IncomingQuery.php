<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomingQuery extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'query_data',
        'client_id',
        'request_id',
        'utm_source',
        'request_data',
        'response_data',
        'ip',
        'user_agent',
    ];

    /**
     * Атрибуты, которые будут преобразованы
     *
     * @var array
     */
    protected $casts = [
        'query_data' => 'object',
        'request_data' => 'object',
        'response_data' => 'object',
    ];

}
