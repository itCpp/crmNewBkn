<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestsStoryOwnPin extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'pin_before',
        'pin_after',
        'is_moscow',
        'date_create',
        'date_uplift',
        'status_id',
        'request_row',
    ];

    /**
     * Атрибуты, которые преобразовываются в json
     *
     * @var array
     */
    protected $casts = [
        'request_row' => 'array',
        'is_moscow' => 'boolean',
    ];
}
