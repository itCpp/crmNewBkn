<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Blocks\BlockList;
use App\Http\Controllers\Admin\Blocks\IpInfos;
use App\Http\Controllers\Admin\Blocks\Statistics;
use App\Http\Controllers\Admin\Blocks\Views;
use App\Http\Controllers\Admin\BlocksDrive\BlockIps;
use App\Models\BlockIp;
use App\Models\Company\BlockHost;
use App\Models\SiteFilter;
use Illuminate\Http\Request;

class Blocks extends Controller
{
    /**
     * Вывод статистики посещений
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function statistic(Request $request)
    {
        return response()->json([
            'rows' => (new Statistics($request))->getStatistic(),
        ]);
    }

    /**
     * Блокировка ip адреса
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function setBlockIp(Request $request)
    {
        if (!$request->ip)
            return response()->json(['message' => "Не указан IP адрес или имя хоста"], 400);

        $request->toArray = true;

        $block = new BlockIps;
        $response = $block->setAll($request);

        return response()->json(array_merge($response, [
            'blocked' => true,
            'blocked_on' => $response['blocks_all'],
        ]));

        // if (!$row = BlockHost::where('host', $request->ip)->first())
        //     $row = BlockHost::create(['host' => $request->ip]);

        // $block = (bool) $row->block;
        // $row->block = (int) !$block;

        // if ($request->has('checked')) {
        //     $row->block = (int) $request->boolean('checked');
        // }

        // $row->save();

        // // Логировние
        // parent::logData($request, $row);

        // return response()->json([
        //     'blocked' => true, // Наличие в черном списке
        //     'blocked_on' => $row->block == 1 ? true : false, // Вкл/Выкл блокировка
        //     'row' => $row,
        // ]);
    }

    /**
     * Информация по IP-адресу и его статистика
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function ipInfo(Request $request)
    {
        return response()->json(
            (new IpInfos($request))->getIpInfoAndStat($request->ip)
        );
    }

    /**
     * Информация по IP-адресу для использования на сторонних сервисах
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getIpInfo(Request $request)
    {
        return response()->json(
            (new IpInfos($request))->getIpInfo($request->ip)
        );
    }

    /**
     * Вывод заблокированных адресов
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getBlockData(Request $request)
    {
        return response()->json(
            BlockList::get($request)
        );
    }

    /**
     * Вывод данных о просмотрах
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getViews(Request $request)
    {
        return response()->json(
            Views::get($request)
        );
    }

    /**
     * Вывод статистики по сайтам из индивидуальных баз
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function allstatistics(Request $request)
    {
        return Blocks\OwnStatistics::get($request);
    }

    /**
     * Добавляет новый параметр utm для сайта
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setutm(Request $request)
    {
        $request->validate([
            'site' => "required|integer",
            'utm' => "required|string",
        ]);

        if (SiteFilter::whereSiteId($request->site)->whereUtmLabel($request->utm)->count())
            return response()->json(['message' => 'Такое значение utm уже существует для данного сайта'], 400);

        $row = SiteFilter::create([
            'site_id' => $request->site,
            'utm_label' => $request->utm,
        ]);

        return response()->json([
            'row' => $row,
        ]);
    }
}
