<?php

namespace App\Http\Controllers\Admin\Blocks;

use App\Http\Controllers\Admin\Databases;
use App\Http\Controllers\Controller;
use App\Models\BlockHost;
use App\Models\BlockIp;
use App\Models\CrmMka\CrmRequestsQueue;
use App\Models\IpInfo;
use App\Models\RequestsQueue;
use App\Models\SettingsQueuesDatabase;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnStatistics extends Controller
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
    public $rows = [];

    /**
     * Данные по статистики сайтов
     * 
     * @var array
     */
    public $site_stats = [];

    /**
     * Создание экземпляра объекта
     * 
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;

        $this->connections = Databases::setConfigs($request->site);

        $this->date = date("Y-m-d");

        $this->sites = [];

        $this->our_ips = explode(",", env("OUR_IP_ADDRESSES_LIST", ""));
    }

    /**
     * Вывод подключений
     * 
     * @return array
     */
    public function connections()
    {
        return $this->connections;
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
     * Вывод информации об IP для блокировки по сайтам
     * 
     * @param \Illuminate\Http\Request $request
     * @param bool $to_array Вернуть массив сайтов
     * @return \Illuminate\Http\JsonResponse
     */
    public function getip(Request $request, $to_array = false)
    {
        if (!$request->ip)
            return response()->json(['message' => "Ошибка в IP адресе"], 400);

        $row = BlockIp::where('ip', $request->ip)->first();

        foreach ($this->connections as $connection) {

            try {
                $block = DB::connection($connection)
                    ->table('blocks')
                    ->where('host', $request->ip)
                    ->where('is_hostname', 0)
                    ->first();

                $autoblock = DB::connection($connection)
                    ->table('automatic_blocks')
                    ->where('ip', $request->ip)
                    ->where('date', $this->date)
                    ->first();

                $site = config("database.connections.{$connection}.site_domain");
                $id = config("database.connections.{$connection}.connection_id");

                $sites[] = [
                    'id' => $id,
                    'site' => $site ?: "Сайт #{$id}",
                    'is_block' => (bool) ($block->is_block ?? null),
                    'is_autoblock' => (bool) $autoblock,
                ];
            } catch (Exception) {
            }
        }

        if ($to_array)
            return $sites ?? [];

        return response()->json([
            'row' => $row,
            'is_period' => (bool) ($row->is_period ?? null),
            'sites' => $sites ?? [],
        ]);
    }

    /**
     * Вывод информации об IP для блокировки по сайтам
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function gethost(Request $request)
    {
        if (!$request->host)
            return response()->json(['message' => "Ошибка в имени хоста"], 400);

        foreach ($this->connections as $connection) {

            try {
                $block = DB::connection($connection)
                    ->table('blocks')
                    ->where('host', $request->host)
                    ->where('is_hostname', 1)
                    ->first();

                $site = config("database.connections.{$connection}.site_domain");
                $id = config("database.connections.{$connection}.connection_id");

                $sites[] = [
                    'id' => $id,
                    'site' => $site ?: "Сайт #{$id}",
                    'is_block' => (bool) ($block->is_block ?? null),
                ];
            } catch (Exception $e) {
                $errors[] = [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ];
            }
        }

        return response()->json([
            'sites' => $sites ?? [],
            'errors' => $errors ?? [],
        ]);
    }

    /**
     * Вывод информации об IP для блокировки по сайтам
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sethost(Request $request)
    {
        if (BlockHost::where('host', $request->hostname)->first())
            return response()->json(['message' => "Данное имя хоста уже существует"], 400);

        $request->validate([
            'hostname' => "required|string",
        ]);

        $row = BlockHost::firstOrNew([
            'host' => $request->host,
        ]);

        $row->host = $request->hostname;
        $row->save();

        $this->logData($request, $row);

        foreach ($this->connections as $connection) {

            try {
                DB::connection($connection)
                    ->table('blocks')
                    ->where('host', $request->host)
                    ->where('is_hostname', 1)
                    ->update([
                        'host' => $request->hostname,
                    ]);
            } catch (Exception $e) {
                $errors[] = [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ];
            }
        }

        return response()->json([
            'hostname' => $request->hostname,
            'errors' => $errors ?? [],
        ]);
    }

    /**
     * Вывод данных статистики по сайтам из индивидуальных баз
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getData(Request $request)
    {
        $rows = $this->getRows();

        foreach ($this->connection_active ?? [] as $connection) {

            $id = config("database.connections.{$connection}.connection_id");
            $site = config("database.connections.{$connection}.site_domain");

            $text = $site ? parse_url($site, PHP_URL_HOST) : null;

            $sites[] = [
                'key' => $id,
                'text' => $text ?: "Сайт #{$id}",
                'value' => $id,
            ];
        }

        return [
            'rows' => $rows,
            'sites' => $sites ?? [],
            'domains' => $this->getDomainsList(),
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
            $this->getStatSite($connection)
                ->getDomains($connection);
        }

        $this->getOtherData();

        return collect($this->rows)
            ->map(function ($row) {
                return $this->serializeRow($row);
            })
            ->sortBy([
                ['blocked_sort', 'desc'],
                ['visits', 'desc'],
                ['drops', 'desc'],
            ])
            ->values()
            ->all();
    }

    /**
     * Формирование строки статистики
     * 
     * @param array $row
     * @return array
     */
    public function serializeRow($row)
    {
        /** Наш IP */
        $row['our_ip'] = in_array($row['ip'], $this->our_ips);

        /** Полная блокировка */
        $blocks_all = (count($this->connection_active) == count($row['block_connections']));
        $row['blocks_all'] = $blocks_all;

        $row['blocked_sort'] = (int) ($row['is_autoblock'] || $row['is_blocked']);

        return $row;
    }

    /**
     * Статистика одного сайта
     * 
     * @param string $connection
     * @return $this
     */
    public function getStatSite($connection)
    {
        try {
            DB::connection($connection)
                ->table('statistics')
                ->when((bool) $this->request->ip, function ($query) {
                    $query->where('ip', $this->request->ip);
                })
                ->where('date', $this->date)
                ->get()
                ->each(function ($row) use ($connection) {

                    $this->ips[] = $row->ip;

                    if (!isset($this->rows[$row->ip]))
                        $this->rows[$row->ip] = $this->createIpRow($row, $connection);

                    if (!isset($this->site_stats[$connection]))
                        $this->site_stats[$connection] = $this->createSiteRow($connection);

                    $this->rows[$row->ip]['visits'] += $row->visits;
                    $this->rows[$row->ip]['visits_drops'] += $row->visits_drops;
                    $this->rows[$row->ip]['requests'] += $row->requests;

                    $this->site_stats[$connection]['visits'] += $row->visits;
                    $this->site_stats[$connection]['requests'] += $row->requests;
                    $this->site_stats[$connection]['visitsBlock'] += $row->visits_drops;
                });

            // if ($site = config("database.connections.{$connection}.site_domain"))
            //     $this->sites[] = parse_url($site, PHP_URL_HOST);

            $this->connection_active[] = $connection;
        } catch (Exception $e) {
            $this->errors[] = [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ];
        }

        return $this;
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
        $domain = config("database.connections.{$connection}.site_domain") ?: config("database.connections.{$connection}.connection_id");

        return [
            'domain' => $domain ? parse_url($domain, PHP_URL_HOST) : null,
            'connection' => $connection,
            'ip' => $row->ip ?? null,
            'host' => $row->hostname ?? null,
            'visits' => 0,
            'visits_drops' => 0,
            'visits_all' => 0,
            'requests' => 0,
            'requests_all' => 0,
            'queues' => 0,
            'queues_all' => 0,
            'is_blocked' => null,
            'is_autoblock' => null,
            'block_connections' => [],
        ];
    }

    /**
     * Формирование строки статистики сайта
     * 
     * @param string $connection
     * @return array
     */
    public function createSiteRow($connection)
    {
        $domain = config("database.connections.{$connection}.site_domain") ?: config("database.connections.{$connection}.connection_id");

        return [
            'domain' => $domain ? parse_url($domain, PHP_URL_HOST) : null,
            'domains' => [],
            'connection' => $connection,
            'visits' => 0,
            'visitsAll' => 0,
            'visitsBlock' => 0,
            'visitsBlockAll' => 0,
            'requests' => 0,
            'requestsAll' => 0,
            'queues' => 0,
            'queuesAll' => 0,
            'is_blocked' => false,
            'is_autoblock' => false,
        ];
    }

    /**
     * Проверка блокировок
     * 
     * @return null
     */
    public function getOtherData()
    {
        $this->ips = [];

        foreach ($this->rows as $row) {
            $this->ips[] = $row['ip'];
        }

        $this->ips = array_unique($this->ips);

        foreach ($this->connections as $connection) {
            $this->checkBlockSiteDataBase($connection);
        }

        $this->getQueuesData();

        /** Информация об IP */
        IpInfo::select('ip', 'country_code', 'region_name', 'city')
            ->whereIn('ip', $this->ips)
            ->get()
            ->each(function ($row) {
                $this->rows[$row->ip]['city'] = $row->city;
                $this->rows[$row->ip]['country_code'] = $row->country_code;
                $this->rows[$row->ip]['region_name'] = $row->region_name;
                $this->rows[$row->ip]['info'] = $row->toArray();
            });

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
        /** Подсчет всех посещений */
        try {
            DB::connection($connection)
                ->table('statistics')
                ->selectRaw('sum(visits + visits_drops) as visits_all, sum(requests) as requests_all, ip, sum(visits_drops) as visits_block_all')
                ->whereIn('ip', $this->ips ?? [])
                ->groupBy('ip')
                ->get()
                ->each(function ($row) use ($connection) {

                    if (!isset($this->rows[$row->ip]))
                        $this->rows[$row->ip] = $this->createIpRow($row, $connection);

                    if (!isset($this->site_stats[$connection]))
                        $this->site_stats[$connection] = $this->createSiteRow($connection);

                    $this->rows[$row->ip]['visits_all'] += $row->visits_all;
                    $this->rows[$row->ip]['requests_all'] += $row->requests_all;

                    $this->site_stats[$connection]['visitsAll'] += $row->visits_all;
                    $this->site_stats[$connection]['requestsAll'] += $row->requests_all;
                    $this->site_stats[$connection]['visitsBlockAll'] += $row->visits_block_all;
                });
        } catch (Exception $e) {
            $this->errors[] = [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ];
        }

        /** Проверка жестких блокировок */
        try {
            DB::connection($connection)
                ->table('blocks')
                ->whereIn('host', $this->ips ?? [])
                ->where('is_block', 1)
                ->get()
                ->each(function ($row) use ($connection) {

                    if (!isset($this->rows[$row->ip]))
                        $this->rows[$row->ip] = $this->createIpRow($row, $connection);

                    $this->rows[$row->host]['block_connections'][] = $connection;
                    $this->rows[$row->host]['is_blocked'] = true;
                });
        } catch (Exception $e) {
            $this->errors[] = [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ];
        }

        /** Проверка автоматических блокировок */
        try {
            DB::connection($connection)
                ->table('automatic_blocks')
                ->whereIn('ip', $this->ips ?? [])
                ->where('date', $this->date)
                ->get()
                ->each(function ($row) use ($connection) {

                    if (!isset($this->rows[$row->ip]))
                        $this->rows[$row->ip] = $this->createIpRow($row, $connection);

                    $this->rows[$row->ip]['is_autoblock'] = true;
                    $this->rows[$row->ip]['is_blocked'] = true;
                });
        } catch (Exception $e) {
            $this->errors[] = [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ];
        }

        return null;
    }

    /**
     * Статистика по очередям
     * 
     * @return $this
     * 
     * @todo Добавить миграцию на создание индексов в таблице очередей
     */
    public function getQueuesData()
    {
        if (env("NEW_CRM_OFF", true))
            return $this->getQueuesFromOldCrm();

        RequestsQueue::selectRaw('count(*) as count, ip')
            ->whereIn('ip', $this->ips)
            ->when(count($this->sites) > 0, function ($query) {
                $query->whereIn('site', $this->sites);
            })
            ->whereBetween('created_at', [
                $this->date . " 00:00:00",
                $this->date . " 23:59:59"
            ])
            ->groupBy('ip')
            ->get()
            ->each(function ($row) {

                if (!isset($this->rows[$row->ip]))
                    $this->rows[$row->ip] = $this->createIpRow($row);

                $this->rows[$row->ip]['queues'] = (int) $row->count;
            });

        RequestsQueue::selectRaw('count(*) as count, ip')
            ->whereIn('ip', $this->ips)
            ->when(count($this->sites) > 0, function ($query) {
                $query->whereIn('site', $this->sites);
            })
            ->groupBy('ip')
            ->get()
            ->each(function ($row) {

                if (!isset($this->rows[$row->ip]))
                    $this->rows[$row->ip] = $this->createIpRow($row);

                $this->rows[$row->ip]['queues_all'] = (int) $row->count;
            });

        return $this;
    }

    /**
     * Статистические данные по очередям из старых таблиц
     * 
     * @return $this
     */
    public function getQueuesFromOldCrm()
    {
        CrmRequestsQueue::selectRaw('count(*) as count, ip')
            ->whereIn('ip', $this->ips)
            ->when(count($this->sites) > 0, function ($query) {
                $query->whereIn('site', $this->sites);
            })
            ->whereBetween('created_at', [
                $this->date . " 00:00:00",
                $this->date . " 23:59:59"
            ])
            ->groupBy('ip')
            ->get()
            ->each(function ($row) {

                if (!isset($this->rows[$row->ip]))
                    $this->rows[$row->ip] = $this->createIpRow($row);

                $this->rows[$row->ip]['queues'] = (int) $row->count;
            });

        CrmRequestsQueue::selectRaw('count(*) as count, ip')
            ->whereIn('ip', $this->ips)
            ->when(count($this->sites) > 0, function ($query) {
                $query->whereIn('site', $this->sites);
            })
            ->groupBy('ip')
            ->get()
            ->each(function ($row) {

                if (!isset($this->rows[$row->ip]))
                    $this->rows[$row->ip] = $this->createIpRow($row);

                $this->rows[$row->ip]['queues_all'] = (int) $row->count;
            });

        return $this;
    }

    /**
     * Блокировка ip на сайте
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setBlockIp(Request $request)
    {
        if (!$row = SettingsQueuesDatabase::find($request->id)) {
            return response()->json([
                'message' => "Настройки базы данных сайта не найдена",
            ]);
        }

        Databases::setConfig([
            'id' => $row->id,
            'host' => $this->decrypt($row->host),
            'port' => $row->port ? $this->decrypt($row->port) : $row->port,
            'database' => $this->decrypt($row->database),
            'user' => $this->decrypt($row->user),
            'password' => $row->password ? $this->decrypt($row->password) : $row->password,
        ]);

        $connection = Databases::getConnectionName($row->id);

        if (!$block_ip = BlockIp::where('ip', $request->ip)->first()) {
            $block_ip = new BlockIp;
            $block_ip->ip = $request->ip;

            if (filter_var($request->ip, FILTER_VALIDATE_IP))
                $block_ip->hostname = gethostbyaddr($request->ip);
        }

        try {
            $model = DB::connection($connection)->table('blocks');
            $block = $model->where('host', $request->ip)
                ->when($block_ip->is_period ?? false, function ($query) {
                    $query->where('is_period', 1);
                })
                ->first();

            $date = date("Y-m-d H:i:s");

            if (!$block) {

                $insert = [
                    'host' => $request->ip,
                    'created_at' => $date,
                    'updated_at' => $date,
                ];

                if ($block_ip->is_period) {
                    $insert = array_merge($insert, [
                        'is_period' => 1,
                        'period_start' => $block_ip->period_data['startLong'] ?? 0,
                        'period_stop' => $block_ip->period_data['stopLong'] ?? 0,
                    ]);
                }

                $block = $model->insert($insert);
            }

            $model->where('host', $request->ip)
                ->update([
                    'is_block' => (int) $request->checked,
                    'updated_at' => $date,
                ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }

        $sites["id-{$row->id}"] = (bool) $request->checked;

        $block_ip->sites = array_merge($block_ip->sites ?: [], $sites ?: []);
        $block_ip->save();

        $this->logData($request, $block_ip);

        return response()->json([
            'row' => $block_ip,
            'message' => $request->ip,
            'is_block' => (bool) $request->checked,
            'connection' => $connection,
        ]);
    }

    /**
     * Блокировка хоста на сайте
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setBlockHost(Request $request)
    {
        if (!$row = SettingsQueuesDatabase::find($request->id)) {
            return response()->json([
                'message' => "Настройки базы данных сайта не найдена",
            ]);
        }

        Databases::setConfig([
            'id' => $row->id,
            'host' => $this->decrypt($row->host),
            'port' => $row->port ? $this->decrypt($row->port) : $row->port,
            'database' => $this->decrypt($row->database),
            'user' => $this->decrypt($row->user),
            'password' => $row->password ? $this->decrypt($row->password) : $row->password,
        ]);

        $connection = Databases::getConnectionName($row->id);

        try {
            $model = DB::connection($connection)->table('blocks');
            $block = $model->where('host', $request->host)
                ->where('is_hostname', 1)
                ->first();

            $date = date("Y-m-d H:i:s");

            if (!$block) {
                $block = $model->insert([
                    'host' => $request->host,
                    'is_hostname' => 1,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }

            $model->where('host', $request->host)
                ->where('is_hostname', 1)
                ->update([
                    'is_block' => (int) $request->checked,
                    'updated_at' => $date,
                ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }

        $block_hosts = BlockHost::firstOrNew(
            ['host' => $request->host],
        );

        $sites["id-{$row->id}"] = (bool) $request->checked;

        $block_hosts->sites = array_merge($block_hosts->sites ?? [], $sites);
        $block_hosts->save();

        $this->logData($request, $block_hosts);

        return response()->json([
            'message' => $request->host,
            'is_block' => (bool) $request->checked,
            'connection' => $connection,
        ]);
    }

    /**
     * Блокировка на всех сайтах одновременно
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setAllBlockIp(Request $request)
    {
        if (!$request->ip)
            return response()->json(['message' => "IP адрес не найден"], 400);

        $hostname = gethostbyaddr($request->ip);
        $date = date("Y-m-d H:i:s");

        foreach ($this->connections as $connection) {

            try {
                $table = DB::connection($connection)->table('blocks');

                $block = $table->where('host', $request->ip)
                    ->where('is_hostname', 0)
                    ->first();

                if (!$block) {
                    $id = $table->insertGetId([
                        'host' => $request->ip,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                } else {
                    $id = $block->id;
                }

                $table->where('id', $id)
                    ->limit(1)
                    ->update([
                        'is_block' => (int) $request->block,
                        'updated_at' => $date,
                    ]);

                $id = config("database.connections.{$connection}.connection_id");
                $sites["id-" . $id] = (bool) $request->block;
            } catch (Exception $e) {
                $errors[] = [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ];
            }
        }

        $row = BlockIp::firstOrNew(['ip' => $request->ip]);
        $row->hostname = $hostname;
        $row->sites = $sites ?? [];

        $row->save();

        $this->logData($request, $row);

        return response()->json([
            'row' => $row,
            'sites' => $sites ?? [],
            'errors' => $errors ?? [],
        ]);
    }

    /**
     * Поиск доменов, по которым был входя на страницу
     * 
     * @param string $connection
     * @return $this
     */
    public function getDomains($connection)
    {
        try {
            DB::connection($connection)
                ->table('visits')
                ->select(
                    'ip',
                    'request_data->headers->host as host',
                    'request_data->headers->Host as host'
                )
                ->whereIn('ip', array_unique($this->ips ?? []))
                // ->whereBetween('created_at', [
                //     $this->date . " 00:00:00",
                //     $this->date . " 23:59:59"
                // ])
                ->distinct()
                ->get()
                ->each(function ($row) use ($connection) {

                    // $data = json_decode($row->request_data, true);

                    $domain = null;

                    if ($row->host ?? null)
                        $domain = $row->host;
                    else if ($row->Host ?? null)
                        $domain = $row->Host;
                    else if ($data['headers']['Host'] ?? null)
                        $domain = $data['headers']['Host'];
                    else if ($data['headers']['host'] ?? null)
                        $domain = $data['headers']['host'];

                    if ($domain) {
                        $this->domains[$row->ip][] = $domain;
                        $this->domains[$connection][] = $domain;
                    }
                });
        } catch (Exception $e) {
            $this->errors[] = [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ];
        }

        return $this;
    }

    /**
     * Вывод списка доменов
     * 
     * @return array
     */
    public function getDomainsList()
    {
        foreach ($this->domains ?? [] as $rows) {
            if (is_array($rows)) {
                foreach ($rows as $domain) {
                    $domains[] = $domain;
                }
            }
        }

        $response = [];

        foreach (array_unique($domains ?? []) as $domain) {
            $response[] = idn_to_utf8($domain);
        }

        sort($response);

        return $response;
    }
}
