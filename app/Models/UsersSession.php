<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsersSession extends Model
{

    use HasFactory, SoftDeletes;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'token',
        'user_id',
        'user_pin',
        'created_at',
        'deleted_at',
        'active_at',
        'ip',
        'user_agent',
        'updated_at',
    ];

}
