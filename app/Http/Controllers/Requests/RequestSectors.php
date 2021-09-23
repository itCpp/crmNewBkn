<?php

namespace App\Http\Controllers\Requests;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\Callcenter;
use App\Models\RequestsRow;
use App\Models\RequestsStory;
use App\Models\RequestsStorySector;

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
        if (!$permits->requests_sector_set OR ($row->callcenter_sector AND !$permits->requests_sector_change))
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

        return response()->json([
            'callcenters' => $callcenters,
            'selected' => $row->callcenter_sector,
            'permits' => $permits,
            'sectors' => $sectors,
        ]);

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
