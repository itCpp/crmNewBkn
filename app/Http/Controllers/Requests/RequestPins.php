<?php

namespace App\Http\Controllers\Requests;

use Illuminate\Http\Request;

use App\Events\Requests\UpdateRequestEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Ratings\CallCenters;
use App\Http\Controllers\Requests\Requests;
use App\Http\Controllers\Users\Notifications;
use App\Http\Controllers\Users\UserData;
use App\Http\Controllers\Users\Worktime;
use App\Models\Office;
use App\Models\Permission;
use App\Models\RequestsRow;
use App\Models\RequestsStory;
use App\Models\RequestsStoryPin;
use App\Models\User;
use App\Models\Notification;
use App\Models\UsersSession;
use App\Models\UserWorkTime;

class RequestPins extends Controller
{
    /**
     * Вывод списка доступных операторов для назначения на заявку
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function changePinShow(Request $request)
    {
        // Проверка наличия заявки
        if (!$row = RequestsRow::find($request->id))
            return response()->json(['message' => "Заявка не найдена"], 400);

        $permits = $request->user()->getListPermits([
            'requests_pin_set', # Может назначать оператора на заявку
            'requests_pin_change', # Может менять оператора в заявке
            'requests_pin_set_offline', # Может назначать оператора, находящегося в офлайне
            'requests_all_sectors', # Видит заявки и операторов всех секторов своего колл-центра
            'requests_all_callcenters', # Видит заявки и операторов всех колл-центров
            'requests_pin_clear', # Удаление оператора из заявки
        ]);

        // Право на назначение оператора
        if (!$permits->requests_pin_set)
            return response()->json(['message' => "Доступ к назначению оператора ограничен"], 403);

        // Право на смену уже назначенного оператора
        if (!$permits->requests_pin_change and $row->pin)
            return response()->json(['message' => "Доступ к смене оператора ограничен"], 403);

        $pins_searched = []; // Список найденных пинов

        // Разрешение, при котором сотрудник отображается в списке выбора операторов
        $permission = Permission::find('requests_pin_for_appointment');

        // Идентификатор сектора и коллцентра администратора
        $permits->__callcenter_id = $request->user()->callcenter_id;
        $permits->__callcenter_sector_id = $request->user()->callcenter_sector_id;

        // Поиск сотрудников, имеющих личное разрешение
        $users = self::findUsers($permission->users(), $permits);

        /** Проверка рейтинга для вывода статистики */
        foreach (optional((new CallCenters($request))->get())->users ?: [] as $user) {
            $rating[$user->pin] = $user;
        }

        foreach ($users as $user) {

            if (!in_array($user->pin, $pins_searched)) {

                $pins_searched[] = $user->pin;

                $pins[] = [
                    'fio' => UserData::createNameFull($user->surname, $user->name, $user->patronymic),
                    'id' => $user->id,
                    'pin' => $user->pin,
                    'callcenter' => $user->callcenter_id,
                    'sector' => $user->callcenter_sector_id,
                ];
            }
        }

        // Поиск сотрудников по ролям, имеющее данное разрешение
        foreach ($permission->roles as $role) {

            $users = self::findUsers($role->users(), $permits, $pins_searched);

            foreach ($users as $user) {

                if (!in_array($user->pin, $pins_searched)) {

                    $pins_searched[] = $user->pin;

                    $pins[] = [
                        'fio' => UserData::createNameFull($user->surname, $user->name, $user->patronymic),
                        'id' => $user->id,
                        'pin' => $user->pin,
                        'callcenter' => $user->callcenter_id,
                        'sector' => $user->callcenter_sector_id,
                        'disabled' => $row->pin == $user->pin,
                        'color' => $row->pin == $user->pin ? "blue" : null,
                        'title' => $row->pin == $user->pin ? "Оператор назначен в заявке" : null,
                    ];
                }
            }
        }

        // Добавление текущего оператора в заявке
        if (!in_array($row->pin, $pins_searched) and $row->pin) {

            $user = User::where('pin', $row->pin)->first();

            $pins_searched[] = $user->pin;

            $pins[] = [
                'fio' => UserData::createNameFull(
                    $user->surname ?? null,
                    $user->name ?? null,
                    $user->patronymic ?? null
                ),
                'id' => $user->id ?? 0,
                'pin' => $user->pin ?? $row->pin,
                'callcenter' => $user->callcenter_id ?? null,
                'sector' => $user->callcenter_sector_id ?? $row->callcenter_sector,
                'disabled' => true,
                'color' => "blue",
                'title' => "Оператор назначен в заявке"
            ];
        }

        foreach ($pins as &$pin) {
            $pin['rating'] = $rating[$pin['pin']] ?? null;
        }

        // Время последней активности пользователя
        $sessions = self::getLastAtiveTime($pins_searched);

        // Список офисов
        $offices = Office::orderBy('active', 'DESC')->orderBy('name')->get();

        // Автоматическое применение адреса, если имется только один активный офис
        if (!$row->address) {

            $actives = [];

            foreach ($offices as $office) {
                if ($office->active == 1)
                    $actives[] = $office->id;
            }

            if (count($actives) == 1)
                $row->address = $actives[0];
        }

        return response()->json([
            'offline' => $permits->requests_pin_set_offline,
            'pins' => self::getWorkTimeAndStatusUsers($pins ?? [], $sessions),
            'clear' => $permits->requests_pin_clear,
            'offices' => $offices,
            'address' => $row->address,
        ]);
    }

    /**
     * Формирование списка сотрудников
     * 
     * @param \Illuminate\Database\Eloquent\Relations\BelongsToMany $rows
     * @param \App\Http\Controllers\Users\Permissions $permits
     * @param array $pins Уже найденные операторы
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findUsers($rows, $permits, $pins = [])
    {
        // Список всех операторов своего колл-центра
        if (!$permits->requests_all_callcenters)
            $rows = $rows->where('callcenter_id', $permits->__callcenter_id);

        // Список операторов своего сектора
        if (!$permits->requests_all_sectors)
            $rows = $rows->where('callcenter_sector_id', $permits->__callcenter_sector_id);

        if (count($pins))
            $rows = $rows->whereNotIn('pin', $pins);

        return $rows->where('deleted_at', null)->get();
    }

    /**
     * Поиск активной сессии сотрудников
     * 
     * @param array $pins Массив пинов, найденных сотрудников
     * @return array
     */
    public static function getLastAtiveTime(array $pins): array
    {
        $data = UsersSession::whereIn('user_pin', $pins)
            ->whereDate('created_at', now())
            ->where('active_at', '>', date("Y-m-d H:i:s", time() - 60 * 15))
            ->get();

        foreach ($data as $row) {
            $sessions[$row->user_pin] = $row->active_at;
        }

        return $sessions ?? [];
    }

    /**
     * Метод поиска статуса пользователя, и сортировка массива
     * 
     * @param array $pins
     * @param array $sessions
     * @return array
     */
    public static function getWorkTimeAndStatusUsers(array $pins, array $sessions): array
    {
        foreach ($pins as $key => &$pin) {

            $pin['active_at'] = $sessions[$pin['pin']] ?? null;

            $worktime = UserWorkTime::where('user_pin', $pin['pin'])
                ->whereDate('date', now())
                ->orderBy('id', "DESC")
                ->first();

            if ($worktime)
                $worktime = Worktime::getDataForEvent($worktime);

            $pin['worktime'] = $worktime['event_type'] ?? null;
            $pin['color'] = $pins[$key]['color'] ?: ($worktime['color'] ?? null);
        }

        usort($pins, function ($a, $b) {
            return $a['pin'] - $b['pin'];
        });

        return $pins;
    }

    /**
     * Изменение оператора в заявке
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function setPin(Request $request)
    {
        // Проверка наличия заявки
        if (!$row = RequestsRow::find($request->id))
            return response()->json(['message' => "Заявка не найдена"], 400);

        if (!$request->addr) {
            return response()->json([
                'message' => "Укажите адрес офиса",
                'errors' => [
                    'addr' => true,
                ]
            ], 400);
        }

        // Разрешения по заявке для пользователя
        RequestStart::$permits = $request->user()->getListPermits(RequestStart::$permitsList);

        // Право на удаление оператора из заявки
        $clear_pin = $request->user()->can('requests_pin_clear');

        // Данные выбранного оператора
        $user = User::find($request->user);

        if (!$clear_pin and !$user)
            return response()->json(['message' => "Оператор не найден"], 400);

        $old = $row->pin;

        $row->pin = $user->pin ?? null;

        if ($user->callcenter_sector_id ?? null)
            $row->callcenter_sector = $user->callcenter_sector_id;

        $row->address = $request->addr;

        $row->save();

        // Логирование изменений заявки
        $story = RequestsStory::write($request, $row);
        RequestsStoryPin::write($story, $old);

        $row = Requests::getRequestRow($row); // Полные данные по заявке
        $row->newPin = $row->pin;
        $row->oldPin = $old;

        // Отправка события об изменении заявки
        broadcast(new UpdateRequestEvent($row));

        // Установка статуса рабочего времени операторам
        Worktime::checkAndWriteWork($row->oldPin);
        Worktime::checkAndWriteWork($row->newPin);

        /** Рассылка уведомлений */
        Notifications::changeRequestPin($row->id, $row->newPin, $row->oldPin);

        return response()->json([
            'request' => $row,
        ]);
    }
}
