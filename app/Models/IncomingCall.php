<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class IncomingCall extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'phone',
        'sip',
        'event_id',
        'locked',
        'added',
        'failed',
    ];

    /**
     * Атрибуты, которые должны быть типизированы.
     *
     * @var array
     */
    // protected $casts = [
    //     'created_at' => 'datetime:Y-m-d H:i:s',
    // ];

    // public function getCreatedAtAttribute($date)
    // {
    //     return date("Y-m-d H:i:s", strtotime($date));
    // }
}
