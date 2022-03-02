<?php

namespace App\Http\Controllers\Admin\Blocks;

use App\Http\Controllers\Admin\Databases;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AllStatistics extends Controller
{
    /**
     * Доступные подключения
     * 
     * @var array
     */
    protected $connections = [];

    /**
     * Данные на вывод
     * 
     * @var mixed
     */
    protected $rows = [];

    /**
     * Создание экземпляра объекта
     * 
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;

        $this->connections = Databases::setConfigs();

        $this->date = date("Y-m-d");
    }

    /**
     * Вывод статистики по сайтам из индивидуальных баз
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    static function get(Request $request)
    {
        $allstatistics = new static($request);

        return response()->json(
            $allstatistics->getData($request),
        );
    }

    /**
     * Вывод данных статистики по сайтам из индивидуальных баз
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getData(Request $request)
    {
        return [
            'rows' => $this->getRows(),
            // 'connections' => $this->connections,
            'errors' => $this->errors ?? null,
        ];
    }

    /**
     * Статистика всех сайтов
     * 
     * @return array
     */
    public function getRows()
    {
        foreach ($this->connections as $connection) {
            $this->getStatSite($connection);
        }

        $this->checkBlock();

        $active = count($this->connection_active);

        return collect($this->rows)->map(function ($row) use ($active) {
            $row['blocks_all'] = ($active == count($row['block_connections']));
            return $row;
        })
            ->values()
            ->all();
    }

    /**
     * Статистика одного сайта
     * 
     * @param string $connection
     * @return null
     */
    public function getStatSite($connection)
    {
        try {
            DB::connection($connection)
                ->table('statistics')
                ->where('date', $this->date)
                ->get()
                ->each(function ($row) use ($connection) {

                    $key = md5($row->ip);

                    if (!isset($this->rows[$key]))
                        $this->rows[$key] = $this->createIpRow($row, $connection);

                    $this->rows[$key]['visits'] += $row->visits;
                    $this->rows[$key]['visits_drops'] += $row->visits_drops;
                    $this->rows[$key]['visits_all'] += ($row->visits + $row->visits_drops);
                });

            $this->connection_active[] = $connection;
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        return null;
    }

    /**
     * Формирование строки статистики
     * 
     * @param stdClass $row
     * @param null|string $connection
     * @return array
     */
    public function createIpRow($row, $connection = null)
    {
        return [
            'connection' => $connection,
            'ip' => $row->ip ?? null,
            'host' => $row->hostname ?? null,
            'visits' => 0,
            'visits_drops' => 0,
            'visits_all' => 0,
            'requests' => 0,
            'queues' => 0,
            'is_blocked' => null,
            'is_autoblock' => null,
            'block_connections' => [],
        ];
    }

    /**
     * Проверка блокировок
     * 
     * @return null
     */
    public function checkBlock()
    {
        $this->ips = [];
        $connections = [];

        foreach ($this->rows as $row) {
            $this->ips[] = $row['ip'];
            $connections[$row['connection']][] = $row['ip'];
        }

        $this->ips = array_unique($this->ips);

        foreach ($connections as $connection => $ips) {
            $this->checkBlockSiteDataBase($connection);
        }

        return null;
    }

    /**
     * Проверка блокировок в базах данных сайтов
     * 
     * @param string $connection
     * @return null
     */
    public function checkBlockSiteDataBase($connection)
    {
        /** Проверка жестких блокировок */
        DB::connection($connection)
            ->table('blocks')
            ->whereIn('host', $this->ips ?? [])
            ->where('is_block', 1)
            ->get()
            ->each(function ($row) use ($connection) {

                $key = md5($row->host);

                $this->rows[$key]['block_connections'][] = $connection;
                $this->rows[$key]['is_blocked'] = true;
            });

        /** Проверка автоматических блокировок */
        DB::connection($connection)
            ->table('automatic_blocks')
            ->whereIn('ip', $this->ips ?? [])
            ->where('date', $this->date)
            ->get()
            ->each(function ($row) {

                $key = md5($row->ip);

                $this->rows[$key]['is_autoblock'] = true;
                $this->rows[$key]['is_blocked'] = true;
            });
    }
}
