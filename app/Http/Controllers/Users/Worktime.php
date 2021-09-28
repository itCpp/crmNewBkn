<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\RequestsRow;
use App\Models\UserWorkTime;
use Illuminate\Http\Request;

class Worktime extends Controller
{

    /**
     * Список событий
     * 
     * @var array
     */
    public static $types = [
        'login', // Авторизация
        'work', // Находится в работе
        'free', // Свободен
        'timeout', // Перерыв
        'lunch', // Обед
        'logout', // Выход из системы
    ];

    /**
     * Список статусов, при котором сотрудник считается готовым к работе
     * 
     * @var array
     */
    public static $free = [
        null,
        'login',
        'free',
    ];

    /**
     * Запись события
     * 
     * @param int $pin
     * @param string $type
     * @return \App\Models\UserWorkTime
     */
    public static function writeEvent($pin, $type)
    {

        $date = now();

        // Предотвращение записи одинакового события
        if ($last = UserWorkTime::whereDate('date', $date)->orderBy('id', 'DESC')->first()) {
            if ($last->event_type == $type)
                return $last;
        }

        return UserWorkTime::create([
            'user_pin' => $pin,
            'event_type' => $type,
            'date' => $date,
            'created_at' => $date,
        ]);
    }

    /**
     * Проверка наличия необработанных заявок и запись статуса
     * 
     * @param null|int $pin
     * @return \App\Models\UserWorkTime|null
     */
    public static function checkAndWriteWork($pin)
    {
        if (!$pin)
            return null;

        $count = RequestsRow::where([
            ['pin', $pin],
            ['status_id', null]
        ])
            ->count();

        if ($count)
            self::writeEvent($pin, 'work');
        else
            self::writeEvent($pin, 'free');
    }
}
