<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmAgreementComment extends Model
{
    use HasFactory;

    /**
     * Соединение с БД, которое должна использовать модель.
     *
     * @var string
     */
    protected $connection = "base";

    /**
     * Наименование таблицы
     * 
     * @var string
     */
    protected $table = "crm_agreement_comment";
}
