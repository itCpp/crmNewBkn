<?php

namespace App\Http\Controllers\Users;

use App\Events\Users\ChangeUserWorkTime;
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
     * Список статусов в работе
     * 
     * @var array
     */
    public static $inWork = [
        'work',
    ];

    /**
     * Список статусов, когда пользователь не активен
     * 
     * @var array
     */
    public static $disabled = [
        'logout',
    ];

    /**
     * Список статусов перерыва
     * 
     * @var array
     */
    public static $timeout = [
        'timeout',
        'lunch',
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

        $last = UserWorkTime::whereDate('date', $date)
            ->whereUserPin($pin)
            ->orderBy('id', 'DESC')
            ->first();

        // Предотвращение записи одинакового события
        if ($last) {
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

        $worktime = self::writeEvent($pin, $count ? 'work' : 'free');
        $user = $worktime->user()->first('id');

        broadcast(new ChangeUserWorkTime($worktime, $user->id ?? null));

        return $worktime;
    }

    /**
     * Определение цвета по текущему статусу
     * 
     * @param null|string $type
     * @return null|string
     */
    public static function getColorButton(null|string $type): null|string
    {
        if (!$type)
            return null;

        // Свободен
        if (in_array($type, self::$free))
            return "green";

        // Статус в работе
        if (in_array($type, self::$inWork))
            return "red";

        // Перив
        if (in_array($type, self::$timeout))
            return "yellow";

        return "grey";
    }
}
