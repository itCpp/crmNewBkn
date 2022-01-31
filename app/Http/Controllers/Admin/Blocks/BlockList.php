<?php

namespace App\Http\Controllers\Admin\Blocks;

use App\Http\Controllers\Controller;
use App\Models\Company\BlockHost;
use App\Models\Company\StatVisit;
use App\Models\IpInfo;
use Illuminate\Http\Request;

class BlockList extends Controller
{
    /**
     * Вывод заблокированных адресов
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public static function get(Request $request)
    {
        $data = BlockHost::when((bool) $request->search, function ($query) use ($request) {
            $query->where('host', 'LIKE', "%{$request->search}%")
                ->orderBy('host');
        })
            ->when((bool) $request->serach === false, function ($query) {
                $query->orderBy('id', "DESC");
            })
            ->when($request->ipv6 and !$request->ipv4, function ($query) {
                $query->where('host', 'like', '%:%');
            })
            ->when(!$request->ipv6 and $request->ipv4, function ($query) {
                $query->where('host', 'not like', '%:%');
            })
            ->paginate(50);

        $rows = [];
        $ips = [];

        $data->each(function ($row) use (&$rows, &$ips) {

            $ips[] = $row->host;

            $row->info = IpInfo::select('country_code', 'region_name', 'city')
                ->where('ip', $row->host)
                ->first();

            $rows[$row->host] = $row->toArray();
        });

        IpInfo::select('ip', 'country_code', 'region_name', 'city')
            ->whereIn('ip', $ips)
            ->get()
            ->each(function ($row) use (&$rows) {
                $rows[$row->ip]['info'] = $row;
            });

        StatVisit::select('ip', 'host')
            ->whereIn('ip', $ips)
            ->distinct()
            ->get()
            ->each(function ($row) use (&$rows) {
                $rows[$row->ip]['hostname'] = $row->host;
            });

        return [
            'rows' => collect($rows)->values()->all(),
            'pages' => $data->lastPage(),
            'next' => $data->currentPage() + 1,
            'page' => $data->currentPage(),
            'total' => $data->total(),
        ];
    }
}
