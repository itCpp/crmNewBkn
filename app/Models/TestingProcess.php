<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestingProcess extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'pin',
        'questions_id',
        'answer_process',
    ];

    /**
     * Атрибуты, которые преобразовываются в json
     *
     * @var array
     */
    protected $casts = [
        'questions_id' => 'array',
        'answer_process' => 'array',
        'start_at' => 'datetime',
        'done_at' => 'datetime',
    ];
}
