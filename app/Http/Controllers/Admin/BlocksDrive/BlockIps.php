<?php

namespace App\Http\Controllers\Admin\BlocksDrive;

use App\Http\Controllers\Admin\Blocks\OwnStatistics;
use App\Http\Controllers\Admin\Databases;
use App\Http\Controllers\Admin\Sites;
use App\Models\BlockIp;
use App\Models\IpInfo;
use Exception;
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
        if ((int) $request->page <= 1) {
            $sites = (new Databases)->sites()->getData()->sites ?? null;
        }

        return response()->json(array_merge(
            [
                'rows' => $this->getBlocks($request),
                'errors' => $this->errors ?? null,
                'sites' => $sites ?? null,
            ],
            $this->paginate ?? []
        ));
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
                $query->where(function ($query) use ($request) {
                    $query->where('ip', 'LIKE', "%{$request->search}%")
                        ->orWhere('hostname', 'LIKE', "%{$request->search}%");
                });
            })
            ->when((bool) $request->ipv4, function ($query) {
                $query->where('ip', 'NOT LIKE', "%:%");
            })
            ->when((bool) $request->ipv6, function ($query) {
                $query->where('ip', 'LIKE', "%:%");
            })
            ->when((bool) $request->site, function ($query) use ($request) {
                $query->where('sites->id-' . $request->site, true);
            })
            ->paginate(60);

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
        try {
            DB::connection($database['connection'] ?? null)
                ->table('blocks')
                ->whereIn('host', $this->ips)
                ->where('is_hostname', 0)
                ->get()
                ->each(function ($row) use ($database) {
                    $this->setRow($row, $database);
                });
        } catch (Exception $e) {
            $this->errors[] = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ];
        }

        try {
            DB::connection($database['connection'] ?? null)
                ->table('automatic_blocks')
                ->whereIn('ip', $this->ips)
                ->where('date', '=', date("Y-m-d"))
                ->get()
                ->each(function ($row) use ($database) {
                    $this->setRowAutomatickBlock($row, $database);
                });
        } catch (Exception $e) {
            $this->errors[] = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ];
        }
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
                'period_data' => $row->period_data ?? null,
                'is_period' => isset($row->is_period) ? ($row->is_period == 1) : false,
                'comment' => $row->comment ?? null,
            ];
        }

        return $this->rows[$ip];
    }

    /**
     * Примение строки с адрсом
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

    /**
     * Применение данных автоблокировки
     * 
     * @param object $row
     * @param array $database
     * @return array
     */
    public function setRowAutomatickBlock($row, $database)
    {
        $this->pushIpRow($row->ip);

        if (!is_integer($row->drop_block ?? null)) {
            $this->rows[$row->ip]['is_autoblock'] = true;
        } else {
            $this->rows[$row->ip]['is_autoblock'] = ($row->drop_block != 1);
            $this->rows[$row->ip]['drop_autoblock'] = $row->drop_block;
        }
        return $this->rows[$row->ip];
    }

    /**
     * Блокировка всех адресов
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setAll(Request $request)
    {
        $row = BlockIp::where('ip', $request->ip)
            ->where('is_period', (int) $request->is_period)
            ->first();

        if (!$row) {
            $row = new BlockIp;

            $row->ip = $request->ip;
            $row->is_period = (int) $request->is_period;

            if ($request->is_period and is_array($request->period_data)) {
                $row->period_data = $request->period_data;
            }

            if (filter_var($request->ip, FILTER_VALIDATE_IP))
                $row->hostname = gethostbyaddr($request->ip);
        }

        $date = date("Y-m-d H:i:s");

        foreach ($this->databases as $database) {

            $databases[] = $database;

            $model = DB::connection($database['connection'] ?? null)->table('blocks');

            $block = $model->where('host', $request->ip)
                ->where('is_period', $row->is_period)
                ->first();

            if (!$block) {

                $insert = [
                    'host' => $request->ip,
                    'is_period' => $row->is_period,
                    'period_start' => $row->period_data['startLong'] ?? null,
                    'period_stop' => $row->period_data['stopLong'] ?? null,
                    'created_at' => $date,
                    'updated_at' => $date,
                ];

                $block = $model->insert($insert);
            }

            $model->where('host', $request->ip)
                ->where('is_period', $row->is_period)
                ->update([
                    'is_block' => (int) $request->checked,
                    'updated_at' => $date,
                ]);

            $sites["id-{$database['id']}"] = (bool) $request->checked;
            $blokeds[$database['id']] = (bool) $request->checked;
        }

        $row->sites = array_merge($row->sites ?: [], $sites ?: []);
        $row->save();

        $this->logData($request, $row);

        $response = [
            'ip' => $row->ip,
            'blocks_all' => (bool) $request->checked,
            'blokeds' => $blokeds ?? [],
            'errors' => $errors ?? null,
            'row' => $row,
        ];

        if ($request->toArray)
            return $response;

        return response()->json($response);
    }
}
