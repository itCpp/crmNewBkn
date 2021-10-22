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
        'timeout_of', // Перерыв окончен
        'lunch', // Обед
        'lunch_of', // Обед окончен
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
     * Список статусов сотрудника в работе
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
     * Список статусов c окончанием перерыва
     * 
     * @var array
     */
    public static $timeoutOf = [
        'timeout_of',
        'lunch_of',
    ];

    /**
     * Список статусов для замены на иконку
     * Наименование иконок используется из библиотеки Semantic UI React
     * @see https://react.semantic-ui.com/elements/icon/
     * 
     * @var array
     */
    public static $typeToIcon = [
        'timeout' => 'coffee',
        'lunch' => 'food',
    ];

    /**
     * Запись события о смене статуса
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
     * @param null|string $timeout
     * @return null|string
     */
    public static function getColorButton(null|string $type, null|string $timeout = null): null|string
    {
        // Перив
        if (in_array($type, self::$timeout) or in_array($timeout, self::$timeout))
            return "yellow";

        if (!$type)
            return null;

        // Свободен
        if (in_array($type, self::$free))
            return "green";

        // Статус в работе
        if (in_array($type, self::$inWork))
            return "red";

        return "grey";
    }

    /**
     * Определение цвета кнопки установки перерыва
     * 
     * @param string|null $status
     * @return string
     */
    public static function getColorButtonTimeout(string|null $status): string
    {
        if (in_array($status, self::$timeout))
            return "red";

        return "green";
    }

    /**
     * Определение активности кнопки установки перерыва
     * Также проверка возможности установить перерыв
     * 
     * @param string|null $status Основной последний статус рабочего времени
     * @param string|null $timeout Последний статус с перерывом
     * @return bool
     */
    public static function getPropDisabledButtonTimeout(string|null $status, string|null $timeout): bool
    {
        if (in_array($timeout, self::$timeout))
            return false;

        if (in_array($status, self::$inWork))
            return true;

        return false;
    }

    /**
     * Вывод массива данных по рабочему времени
     * 
     * @param \App\Models\UserWorkTime $row
     * @param bool $timeout Флаг необходимости поиска "перивного" статуса
     * @return array
     */
    public static function getDataForEvent(UserWorkTime $row, bool $timeout = true)
    {
        // Цвет основной иконки с портфелем
        $row->color = self::getColorButton($row->event_type);

        // Добавление иформации о кнопке
        if ($timeout) {
            $row->timeout_icon = self::getLastTimeoutStatus($row->user_pin);
            $row = self::addepdTimeoutData($row);
        }

        return $row->toArray();
    }

    /**
     * Поиск последнего статуса с перерывом
     * 
     * @param int|string $pin
     * @return string|null
     */
    public static function getLastTimeoutStatus(int|string $pin): string|null
    {
        // Поиск последнего статуса с перерывом
        $timeout = UserWorkTime::whereDate('date', now())
            ->whereUserPin($pin)
            ->whereIn('event_type', array_merge(self::$timeout, self::$timeoutOf))
            ->orderBy('id', 'DESC')
            ->first();

        return $timeout->event_type ?? null;
    }

    /**
     * Добавление информации о перерыве
     * 
     * @param \App\Models\UserWorkTime $row
     * @return \App\Models\UserWorkTime
     */
    public static function addepdTimeoutData($row)
    {
        // Определение цвета иконки установки перерыва
        $row->timeout_color = self::getColorButtonTimeout($row->timeout_icon);

        // Определение свойства дизактивации для икноки смены перерыва
        $row->timeout_disabled = self::getPropDisabledButtonTimeout($row->timeout_icon, $row->event_type);

        // Определение иконки
        $row->timeout_icon = in_array($row->timeout_icon, self::$timeoutOf)
            ? "clock" : $row->timeout_icon;

        // Перепроверка цвета иконки основного статуса с учетом перерыва
        $row->color = self::getColorButton($row->event_type, $row->timeout_icon);

        // Подмена статусов на иконки
        $row->timeout_icon = self::$typeToIcon[$row->timeout_icon] ?? $row->timeout_icon;

        return $row;
    }

    /**
     * Ручная установка статуса сотрудника
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function setWorkTime(Request $request)
    {
        $date = now();

        if (!in_array($request->type, self::$types))
            return response()->json(['message' => "Неправильный статус"], 400);

        // Поиск последнего статуса с перерывом
        $timeoutLast = self::getLastTimeoutStatus($request->user()->pin);

        // Определение последнего статуса сотрудника до перерыва
        $last = UserWorkTime::whereDate('date', $date)
            ->whereUserPin($request->user()->pin)
            ->whereNotIn('event_type', array_merge(self::$timeout, self::$timeoutOf))
            ->orderBy('id', 'DESC')
            ->first();

        // Проверка возможности уйти на перерыв
        if ($last and !in_array($timeoutLast, self::$timeoutOf)) {

            $last = self::getDataForEvent($last);

            if ($last['timeout_disabled'])
                return response()->json(['message' => "Вы не можете уйти на перерыв, скорее всего у Вас имеются необработанные заявки"], 400);
        }

        // Начало перерыва
        if (!in_array($timeoutLast, self::$timeout)) {
            $row = UserWorkTime::create([
                'user_pin' => $request->user()->pin,
                'event_type' => $request->type,
                'date' => $date,
                'created_at' => $date,
            ]);
        }
        // Окончание перерыва
        else {
            // Запись об окончании перерыва
            UserWorkTime::create([
                'user_pin' => $request->user()->pin,
                'event_type' => $request->type . "_of",
                'date' => $date,
                'created_at' => $date,
            ]);

            $oldToNew = $last['event_type'] ?? "free";

            // Запись последнего статуса активного статуса
            $row = UserWorkTime::create([
                'user_pin' => $request->user()->pin,
                'event_type' => in_array($oldToNew, self::$free) ? "free" : $oldToNew,
                'date' => $date,
                'created_at' => $date,
            ]);
        }

        return response()->json([
            'worktime' => self::getDataForEvent($row),
            '$timeoutLast' => $timeoutLast,
        ]);
    }
}
