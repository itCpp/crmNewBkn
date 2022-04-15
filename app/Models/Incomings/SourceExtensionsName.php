<?php

namespace App\Models\Incomings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SourceExtensionsName extends Model
{
    use HasFactory;

    /**
     * Соединение с БД, которое должна использовать модель.
     *
     * @var string
     */
    protected $connection = "incomings";

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'extension',
        'abbr_name',
    ];
}
