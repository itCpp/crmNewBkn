<?php

namespace App\Models\CrmMka;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmNewRequestsStory extends Model
{
    use HasFactory;

    /**
     * Соединение с БД, которое должна использовать модель.
     *
     * @var string
     */
    protected $connection = "mka";

    /**
     * Наименование таблицы
     * 
     * @var string
     */
    protected $table = "crm_new_requests_story";

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}
