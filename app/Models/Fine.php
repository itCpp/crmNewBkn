<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fine extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_pin',
        'from_pin',
        'fine',
        'comment',
        'request_id',
        'is_autofine',
        'fine_date',
    ];

    /**
     * Attributes to be typed.
     *
     * @var array
     */
    protected $casts = [
        'is_autofine' => 'boolean',
        'fine_date' => 'datetime:Y-m-d',
    ];
}
