<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestsRowsConfirmedComment extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'request_id',
        'confirmed',
        'confirm_pin',
    ];

    /**
     * Атрибуты, которые будут преобразованы
     *
     * @var array
     */
    protected $casts = [
        'confirmed' => 'boolean',
    ];
}
