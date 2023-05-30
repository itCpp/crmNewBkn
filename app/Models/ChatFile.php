<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatFile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'hash',
        'name',
        'original_name',
        'path',
        'type',
        'mime_type',
        'size',
        'duration',
        'created_at',
        'updated_at',
    ];
}
