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
use App\Models\SiteIpHide;
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
     * Добавляет новый параметры фильтра для сайта
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setutm(Request $request)
    {
        $request->validate([
            'site' => "required|integer",
            // 'utm' => "required|string",
        ]);

        if ((bool) $request->utm) {
            $utm = $this->createFilterLabel($request->site, $request->utm, 'utm_label');
            parent::logData($request, $utm);
        }

        if ((bool) $request->refferer) {
            $refferer = $this->createFilterLabel($request->site, $request->refferer, 'refferer_label');
            parent::logData($request, $refferer);
        }

        return response()->json([
            'utm' => $utm ?? null,
            'refferer' => $refferer ?? null,
        ]);
    }

    /**
     * Добавляет новый параметр utm для сайта
     * 
     * @param  int $site_id
     * @param  string $value
     * @param  string $column
     * @return \App\Models\SiteFilter
     */
    public function createFilterLabel($site_id, $value, $column)
    {
        return SiteFilter::firstOrCreate([
            'site_id' => $site_id,
            $column => $value,
        ]);
    }

    /**
     * Вывод списка utm фильтров для сайта
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getutm(Request $request)
    {
        return response()->json([
            'filters' => SiteFilter::whereSiteId($request->site)->get(),
        ]);
    }

    /**
     * Удаляет строку фильтра
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function droputm(Request $request)
    {
        if (!$row = SiteFilter::find($request->id))
            return response()->json(['message' => "Строка не найдена или уже удалена"], 400);

        $row->delete();

        parent::logData($request, $row);

        return response()->json([
            'row' => $row,
        ]);
    }

    /**
     * Скрывает или отображает ip для вывода в таблице
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setHideIp(Request $request)
    {
        $row = SiteIpHide::where([
            'site_id' => $request->site,
            'ip' => $request->ip,
        ])->first();

        if ($row) {

            $row->delete();

            $row->site_id = null;
            $row->ip = null;
        } else {

            $row = SiteIpHide::create([
                'site_id' => $request->site,
                'ip' => $request->ip,
            ]);
        }

        parent::logData($request, $row);

        return response()->json([
            'row' => $row,
            'ip' => $request->ip,
            'is_hide' => (bool) $row->ip,
        ]);
    }
}
