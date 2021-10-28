<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingsGlobal extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'value',
        'type',
        'comment',
        'created_at',
        'updated_at',
    ];
}
