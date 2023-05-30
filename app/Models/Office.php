<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'base_id',
        'active',
        'name',
        'addr',
        'address',
        'sms',
        'statuses',
        'tel',
        'settings',
    ];

    /**
     * Атрибуты, которые преобразовываются в json
     *
     * @var array
     */
    protected $casts = [
        'active' => "int",
        'statuses' => 'array',
        'settings' => 'object',
    ];
}
