<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallDetailRecord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'event_id',
        'phone',
        'phone_hash',
        'extension',
        'operator',
        'path',
        'call_at',
        'type',
        'duration',
        'created_at',
        'updated_at',
    ];
}
