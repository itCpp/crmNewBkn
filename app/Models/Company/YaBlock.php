<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class YaBlock extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Название соединения для модели.
     *
     * @var string
     */
    protected $connection = 'company';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'site',
        'ip',
        'ip_rang_start',
        'ip_rang_stop',
        'hostname_part',
    ];
}
