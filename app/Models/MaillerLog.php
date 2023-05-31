<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaillerLog extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'mailler_id',
        'request_data',
        'response_data',
        'start_at',
        'send_at',
        'is_send',
        'failed_at',
        'is_failed',
    ];

    /**
     * Атрибуты, которые будут преобразованы
     *
     * @var array
     */
    protected $casts = [
        'request_data' => "array",
        'response_data' => "boolean",
        'start_at' => "datetime",
        'send_at' => "datetime",
        'is_send' => "boolean",
        'failed_at' => "datetime",
        'is_failed' => "boolean",
    ];
}
