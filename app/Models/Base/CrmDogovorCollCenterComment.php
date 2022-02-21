<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmDogovorCollCenterComment extends Model
{
    use HasFactory;

    /**
     * Название соединения для модели.
     *
     * @var string
     */
    protected $connection = 'base';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_row',
        'pin',
        'comment',
    ];
}
