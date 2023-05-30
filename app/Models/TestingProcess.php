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
        'pin_old',
        'questions_id',
        'answer_process',
        'created_at',
        'start_at',
        'done_at',
        'updated_at',
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
