<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllVisit extends Model
{
    use HasFactory;

    /**
     * Название соединения для модели.
     *
     * @var string
     */
    protected $connection = 'company';

    /**
     * Определяет необходимость отметок времени для модели.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Атрибуты, которые преобразовываются в json
     *
     * @var array
     */
    protected $casts = [
        'other_data' => 'array',
    ];
    
}
