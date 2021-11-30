<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatVisit extends Model
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
    
}
