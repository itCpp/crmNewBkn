<?php

namespace App\Http\Controllers\Admin\BlocksDrive;

use App\Models\BlockIp;
use App\Models\IpInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlockIps extends Drive
{
    /**
     * Список заблокированных адресов
     * 
     * @var array
     */
    public $rows = [];

    /**
     * Список ip адресов
     * 
     * @var array
     */
    protected $ips = [];

    /**
     * Вывод заблокированных Хостов
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json(array_merge([
            'rows' => $this->getBlocks($request),
        ], $this->paginate ?? []));
    }

    /**
     * Поиск заблокированных адресов во всех базах сайта
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getBlocks(Request $request)
    {
        $paginate = BlockIp::orderBy('id', 'DESC')
            ->when((bool) $request->search, function ($query) use ($request) {
                $query->where('ip', 'LIKE', "%{$request->search}%");
            })
            ->when((bool) $request->ipv4, function ($query) {
                $query->where('ip', 'NOT LIKE', "%:%");
            })
            ->when((bool) $request->ipv6, function ($query) {
                $query->where('ip', 'LIKE', "%:%");
            })
            ->paginate(40);

        $this->paginate = [
            'total' => $paginate->total(),
            'page' => $paginate->currentPage(),
            'pages' => $paginate->lastPage(),
        ];

        $this->ips = $paginate->map(function ($row) {
            $this->pushIpRow($row);
            return $row->ip;
        })->toArray();

        foreach ($this->databases as $database) {
            $this->getBlock($database);
        }

        IpInfo::whereIn('ip', $this->ips)->get()
            ->each(function ($row) {
                $this->ip_infos[$row->ip] = $row;
            });

        return collect($this->rows)
            ->map(function ($row) {
                return $this->setResultRow($row);
            })
            ->values()
            ->all();
    }

    /**
     * Финальная обработка строки
     * 
     * @param array $row
     * @return array
     */
    public function setResultRow($row)
    {
        $blocked = 0;

        foreach ($this->databases as $database) {

            $is_block = $row['blocks'][$database['id']] ?? false;

            if ($is_block)
                $blocked++;

            $blocks[] = [
                'site' => $database['domain'] ?: "Сайт #" . $database['id'],
                'block' => $is_block,
                'id' => $database['id'],
            ];
        }

        if (count($this->connections) == $blocked)
            $row['blocks_all'] = true;

        $row['blocks'] = $blocks ?? [];

        $row['info'] = $this->ip_infos[$row['ip']] ?? null;

        return $row;
    }

    /**
     * Поиск заблокированных адресов в базе сайта
     * 
     * @param string $database
     */
    public function getBlock($database)
    {
        DB::connection($database['connection'] ?? false)
            ->table('blocks')
            ->whereIn('host', $this->ips)
            ->where('is_hostname', 0)
            ->get()
            ->each(function ($row) use ($database) {
                $this->setRow($row, $database);
            });
    }

    /**
     * Создание строки c IP сдресом
     * 
     * @param string|BlockIp $row
     * @return array
     */
    public function pushIpRow($row)
    {
        $ip = $row instanceof BlockIp ? $row->ip : $row;

        if (empty($this->rows[$ip])) {
            $this->rows[$ip] = [
                'ip' => $ip,
                'hostname' => $row->hostname ?? null,
                'blocks' => [],
                'is_blocked' => false,
                'blocks_all' => false,
                'is_period' => isset($row->is_period) ? ($row->is_period == 1) : false,
            ];
        }

        return $this->rows[$ip];
    }

    /**
     * Примение строки с адрсеом
     * 
     * @param object $row
     * @param array $database
     * @return array
     */
    public function setRow($row, $database)
    {
        $this->pushIpRow($row->host);

        if ($row->is_block == 1)
            $this->rows[$row->host]['is_blocked'] = true;

        $this->rows[$row->host]['blocks'][$database['id']] = $row->is_block == 1;

        return $this->rows[$row->host];
    }
}
