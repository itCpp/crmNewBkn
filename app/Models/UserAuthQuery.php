<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAuthQuery extends Model
{
    use HasFactory, SoftDeletes;

    const DELETED_AT = 'done_at';

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'callcenter_id',
        'sector_id',
        'ip',
        'user_agent',
        'done_pin',
    ];

    /**
     * Поля типа Carbon
     * 
     * @var array
     */
    protected $dates = [
        'done_at',
    ];
}
