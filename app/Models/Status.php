<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'theme',
        'zeroing',
        'zeroing_data',
        'settings',
    ];

    /**
     * Атрибуты, которые следует преобразовать
     * `zeroing_data` не определять, так как в коде используется принудительное
     * преобразование
     * 
     * @var array
     */
    protected $casts = [
        'settings' => "object",
    ];
}
