<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class RequestsComment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'request_id',
        'type_comment',
        'created_pin',
        'comment',
    ];

    /**
     * Поля типа Carbon
     * 
     * @var array
     */
    protected $dates = [
        'deleted_at',
    ];
}
