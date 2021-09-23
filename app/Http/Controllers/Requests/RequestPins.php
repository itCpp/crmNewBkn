<?php

namespace App\Http\Controllers\Requests;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Requests\Requests;
use App\Models\Permission;
use App\Models\RequestsRow;
use App\Models\RequestsStory;
use App\Models\RequestsStoryPin;
use App\Models\User;

class RequestPins extends Controller
{

    /**
     * Вывод списка доступных операторов для назначения на заявку
     * 
     * @param \Illuminate\Http\Request  $request
     * @return response
     */
    public static function changePinShow(Request $request)
    {

        // Проверка наличия заявки
        if (!$row = RequestsRow::find($request->id))
            return response()->json(['message' => "Заявка не найдена"], 400);

        $permits = $request->__user->getListPermits([
            'requests_pin_set', # Может назначать оператора на заявку
            'requests_pin_change', # Может менять оператора в заявке
            'requests_pin_set_offline', # Может назначать оператора, находящегося в офлайне
            'requests_all_sectors', # Видит заявки и операторов всех секторов своего колл-центра
            'requests_all_callcenters', # Видит заявки и операторов всех колл-центров
            'requests_pin_clear', # Удаление оператора из заявки
        ]);

        if (!$permits->requests_pin_set)
            return response()->json(['message' => "Доступ к назначению оператора ограничен"], 403);

        if (!$permits->requests_pin_change AND $row->pin)
            return response()->json(['message' => "Доступ к смене оператора ограничен"], 403);
        
        $pins_searched = []; // Список найденных пинов

        // Разрешение, при котором разрешен вывод сотрудника в списке выбора оператора
        $permission = Permission::find('requests_pin_for_appointment');

        // Идентификатор сектора и коллцентра
        $permits->__callcenter_id = $request->__user->callcenter_id;
        $permits->__callcenter_sector_id = $request->__user->callcenter_sector_id;

        // Поиск сотрудников, имеющих личное разрешение
        $users = RequestPins::findUsers($permission->users(), $permits);

        foreach ($users as $user) {
            
            if (!in_array($user->pin, $pins_searched)) {

                $pins_searched[] = $user->pin;

                $pins[] = [
                    'id' => $user->id,
                    'pin' => $user->pin,
                    'callcenter' => $user->callcenter_id,
                    'sector' => $user->callcenter_sector_id,
                ];

            }

        }

        // Поиск сотрудников по ролям, имеющее данное разрешение
        foreach ($permission->roles as $role) {

            $users = RequestPins::findUsers($role->users(), $permits, $pins_searched);

            foreach ($users as $user) {
            
                if (!in_array($user->pin, $pins_searched)) {
    
                    $pins_searched[] = $user->pin;
    
                    $pins[] = [
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
        if (!in_array($row->pin, $pins_searched) AND $row->pin) {

            $user = User::where('pin', $row->pin)->first();

            $pins[] = [
                'id' => $user->id ?? 0,
                'pin' => $user->pin ?? $row->pin,
                'callcenter' => $user->callcenter_id ?? null,
                'sector' => $user->callcenter_sector_id ?? $row->callcenter_sector,
                'disabled' => true,
                'color' => "blue",
                'title' => "Оператор назначен в заявке"
            ];
        }

        return response()->json([
            'offline' => $permits->requests_pin_set_offline,
            'pins' => self::getWorkTimeAndStatusUsers($pins ?? []),
            'clear' => $permits->requests_pin_clear,
        ]);

    }

    /**
     * Формирование списка сотрудников
     * 
     * @param \Illuminate\Database\Eloquent\Relations\BelongsToMany $rows
     * @param \App\Http\Controllers\Users\Permissions $permits
     * @param array $pins Уже найденные операторы
     * @return array
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

        return $rows->get();

    }

    /**
     * Метод поиска статуса пользователя, и сортировка массива
     * 
     * @param array
     * @return array
     */
    public static function getWorkTimeAndStatusUsers($pins = [])
    {

        usort($pins, function($a, $b) {
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

        // Разрешения по заявке для пользователя
        RequestStart::$permits = $request->__user->getListPermits(RequestStart::$permitsList);

        // Право на удаление оператора из заявки
        $clear_pin = $request->__user->can('requests_pin_clear');

        // Данные выбранного оператора
        $user = User::find($request->user);

        if (!$clear_pin AND !$user)
            return response()->json(['message' => "Оператор не найден"], 400);

        $old = $row->pin;

        $row->pin = $user->pin ?? null;

        if ($user)
            $row->callcenter_sector = $user->callcenter_sector_id ?? null;

        $row->save();

        // Логирование изменений заявки
        $story = RequestsStory::write($request, $row);
        RequestsStoryPin::write($story, $old);

        return response()->json([
            'request' => Requests::getRequestRow($row),
        ]);

    }

}