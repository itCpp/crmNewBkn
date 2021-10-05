<?php

namespace App\Http\Controllers\Requests;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\Worktime;
use App\Models\Callcenter;
use App\Models\RequestsRow;
use App\Models\RequestsStory;
use App\Models\RequestsStorySector;
use App\Models\User;
use App\Models\UsersSession;

class RequestSectors extends Controller
{

    /**
     * Вывод списка секторов для выдачи заявки в нужный сектор
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function changeSectorShow(Request $request)
    {

        // Проверка наличия заявки
        if (!$row = RequestsRow::find($request->id))
            return response()->json(['message' => "Заявка не найдена"], 400);

        // Проверка необходимых разрешений
        $permits = $request->__user->getListPermits([
            'requests_sector_set', # Может назначать заявку для сектора
            'requests_sector_change', # Может менять сектор в заявке
            'requests_sector_clear', # Может обнулить сектор
            'requests_all_callcenters', # Видит заявки и операторов всех колл-центров	
            'requests_all_sectors', # Видит заявки и операторов всех секторов своего колл-центра
        ]);

        // Проверка прав
        if (!$permits->requests_sector_set or ($row->callcenter_sector and !$permits->requests_sector_change))
            return response()->json(['message' => "Доступ ограничен"], 403);

        // Поиск секторов
        $callcenters = Callcenter::where('active', 1);

        if (!$permits->requests_all_callcenters)
            $callcenters = $callcenters->where('id', $request->__user->callcenter_id);

        $callcenters = $callcenters->get();

        $sectors = [];

        foreach ($callcenters as &$callcenter) {

            foreach ($callcenter->sectors as $sector)
                $sectors[] = $sector;
        }

        $stats = self::getStatsForSectors(collect($sectors));

        foreach ($callcenters as &$callcenter) {
            foreach ($callcenter->sectors as &$sector) {
                $sector->requests = $stats[$sector->id]['requests'] ?? 0;
                $sector->online = $stats[$sector->id]['online'] ?? 0;
                $sector->free = $stats[$sector->id]['free'] ?? 0;
            }
        }

        return response()->json([
            'callcenters' => $callcenters,
            'selected' => $row->callcenter_sector,
            'permits' => $permits,
            'stats' => $stats
        ]);
    }

    /**
     * Статистистика по секторам
     * 
     * @param \Illuminate\Support\Collection $sectors
     * @return array
     */
    public static function getStatsForSectors($sectors)
    {

        $ids = $sectors->map(function ($row) {
            return $row->id;
        });

        $data = [];

        foreach ($ids as $id) {
            $data[$id] = [
                'requests' => 0,
                'online' => 0,
                'free' => 0,
            ];
        }

        // Количество заявок
        $requests = RequestsRow::selectRaw('COUNT(*) as count, callcenter_sector')
            ->whereIn('callcenter_sector', $ids)
            ->whereDate('created_at', now())
            ->groupBy('callcenter_sector')
            ->get();

        foreach ($requests as $row) {
            $data[$row->callcenter_sector]['requests'] = $row->count;
        }

        // Колчество онлайн пользователей
        $users = UsersSession::whereDate('active_at', now())
            ->get()
            ->map(function ($row) {
                return $row->user_pin;
            });

        // Сектора сотрудников онлайн
        $users_sectors = User::select('pin', 'callcenter_sector_id')
            ->whereIn('pin', $users)
            ->get();

        foreach ($users_sectors as $row) {

            if (!$row->callcenter_sector_id)
                continue;

            $data[$row->callcenter_sector_id]['online']++;

            if ($worktime = $row->worktime()->orderBy('id', 'DESC')->first()) {

                if (in_array($worktime->event_type, Worktime::$free))
                    $data[$row->callcenter_sector_id]['free']++;

                $data['worktimes'][$worktime->user_pin] = $worktime->event_type;
            }
        }

        return $data;
    }

    /**
     * Передача заявки сектору
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function setSector(Request $request)
    {

        // Проверка наличия заявки
        if (!$row = RequestsRow::find($request->id))
            return response()->json(['message' => "Заявка не найдена"], 400);

        // Разрешения по заявке для пользователя
        RequestStart::$permits = $request->__user->getListPermits(RequestStart::$permitsList);

        $old = $row->callcenter_sector;

        $row->callcenter_sector = $request->sector;
        $row->save();

        // Логирование изменений заявки
        $story = RequestsStory::write($request, $row);
        RequestsStorySector::write($story, $old);

        return response()->json([
            'request' => Requests::getRequestRow($row),
        ]);
    }
}
