<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersViewPart extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Поля типа Carbon
     * 
     * @var array
     */
    protected $dates = [
        'view_at',
    ];

    /**
     * Возвращает экземпляр модели по параметрам
     * 
     * @param int $user_id
     * @param string $part_name
     * @return UsersViewPart
     */
    public static function getLastTime($user_id, $part_name)
    {
        $view = new static;

        if ($last = $view->whereUserId($user_id)->wherePartName($part_name)->first())
            return $last;

        $view->part_name = $part_name;
        $view->user_id = $user_id;

        return $view;
    }
}
