<?php

namespace App\Http\Controllers\Requests;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Requests\Requests;
use App\Models\Permission;
use App\Models\RequestsRow;
use App\Models\RequestsStory;
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
            'requests_pin_set_all_sectors', # Видит список операторов всех секторов своего колл-центра
            'requests_pin_set_all_callcenters', # Видит список операторов всех колл-центров
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
                    ];
    
                }
    
            }

        }

        return response()->json([
            'offline' => $permits->requests_pin_set_offline,
            'pins' => $pins ?? [],
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
        if (!$permits->requests_pin_set_all_callcenters)
            $rows = $rows->where('callcenter_id', $permits->__callcenter_id);

        // Список операторов своего сектора
        if (!$permits->requests_pin_set_all_sectors)
            $rows = $rows->where('callcenter_sector_id', $permits->__callcenter_sector_id);

        if (count($pins))
            $rows = $rows->whereNotIn('pin', $pins);

        return $rows->get();

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

        // Право на удаление оператора из заявки
        $clear_pin = $request->__user->can('requests_pin_clear');

        $user = User::find($request->user);

        if (!$clear_pin AND !$user)
            return response()->json(['message' => "Оператор не найден"], 400);

        $row->pin = $user->pin ?? null;
        $row->save();

        // Логирование изменений заявки
        RequestsStory::write($request, $row);

        // Разрешения для пользователя
        RequestStart::$permits = $request->__user->getListPermits(RequestStart::$permitsList);

        return response()->json([
            'request' => Requests::getRequestRow($row),
        ]);

    }

}