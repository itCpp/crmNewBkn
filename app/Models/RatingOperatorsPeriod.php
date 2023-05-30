<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingOperatorsPeriod extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'date',
        'pin',
        'comings',
        'requests',
        'requests_all',
        'efficiency',
        'cashbox',
        'loading',
    ];
}
