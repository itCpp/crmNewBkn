<?php

namespace App\Console\Commands;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings;
use App\Http\Controllers\Queues\QueueProcessings;
use App\Http\Controllers\Requests\Queues;
use App\Models\RequestsQueue;
use App\Models\SettingsQueuesDatabase;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequestsGetFromSitesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'requests:getfromsite
                            {--while : Запустить обработку в цикле}
                            {--sleep=15 : Время паузы между проходами цикла}
                            {--locked : Блокировать строки в базе сайта}
                            {--delete : Удалять строки после добавления в очередь}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check requests queue from sites databases';

    /**
     * Подключения
     * 
     * @var array
     */
    protected $databases;

    /**
     * Настройки автоматического добавления заявок по источникам
     * 
     * @var array
     */
    protected $auto_sources = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->log = Log::channel('check_queues');

        if (env('NEW_CRM_OFF', true)) {
            $this->log->warning("Добавление заявок отключено до переноса");
            $this->line(date("[Y-m-d H:i:s]") . " <error>Добавление заявок отключено до переноса</error>");

            return 0;
        }

        $this->start = $this->last = microtime(1);

        $this->databases = SettingsQueuesDatabase::getAllDecrypt();
        $this->settings = new Settings('TEXT_REQUEST_AUTO_ADD');

        if (!count($this->databases)) {
            $this->log->warning("Подключения к базам данных не настроены");
            $this->line(date("[Y-m-d H:i:s]") . " <error>Подключения к базам данных не настроены</error>");

            return 0;
        }

        $this->setDatabasesConfigurations();

        $this->while = $this->option('while');
        $this->sleep = (int) $this->option('sleep');
        $this->locked = $this->option('locked');
        $this->delete = $this->option('delete');

        /** Принудительная блокировка и удаление строк после перехода в новую ЦРМ */
        if (!env('NEW_CRM_OFF', true)) {

            if (!$this->locked)
                $this->locked = true;

            if (!$this->delete)
                $this->delete = true;
        }

        $this->handleStep();

        if (!$this->while)
            return 0;

        while ($this->start > microtime(1) - 50) {

            if (microtime(1) - $this->last < $this->sleep)
                continue;

            $this->handleStep();

            $this->last = microtime(1);
        }

        return 0;
    }

    /**
     * Выполнение одного прохода
     * 
     * @return mixed $this
     */
    public function handleStep()
    {
        foreach ($this->databases as $db) {
            $this->dbConnection($db);
        }

        return $this;
    }

    /**
     * Вывод ошибки при подключении к БД
     * 
     * @param  string $connection
     * @param  object $e
     * @return $this
     */
    public function exceptionWrite($connection, $e)
    {
        $this->log->error("[{$connection}] Ошибка: {$e->getMessage()}");
        $this->line(date("[Y-m-d H:i:s]") . "[{$connection}] Ошибка: <error>{$e->getMessage()}</error>");

        return $this;
    }

    /**
     * Подключение к базам данных и получение данных
     * 
     * @param array $db
     * @return mixed $this
     */
    public function dbConnection($db)
    {
        $connection = "mysql_queue_{$db['id']}";

        try {

            $table = DB::connection($connection)
                ->table($db['table_name'] ?? 'queue_requests');

            if ($this->locked)
                $where = $table->where('locked', 0);

            $data = ($where ?? $table)->get();

            $this->log->info("[{$connection}] " . count($data) . " {$db['name']}");
            $this->line(date("[Y-m-d H:i:s]") . "[{$connection}][" . count($data) . "][<info>{$db['name']}</info>]");
        } catch (Exception $e) {
            return $this->exceptionWrite($connection, $e);
        }

        if (!count($data))
            return $this;

        // Блокировка строк
        if ($this->locked) {

            $this->line("[{$connection}] Строки заблокированы для изменения");

            $table->whereIn('id', $data->map(function ($row) {
                return $row->id;
            }))->update([
                'locked' => 1,
            ]);
        }

        foreach ($data as $row) {

            $queue = $this->createQueue($row);

            // Удаление строки
            $deleted = "NOT DELETED";

            // Автоматическое добавление заявки
            $added = "NOT ADDED";

            if ($this->settings->TEXT_REQUEST_AUTO_ADD || $this->checkAutoDoneResource($queue->site)) {
                $added = "ADDED";
                (new QueueProcessings($queue))->add();
            }

            if ($this->delete) {
                $table->where('id', $row->id)->limit(1)->delete();
                $deleted = "DELETED";
            }

            $this->log->notice("[{$connection}][{$row->id}][{$deleted}][{$queue->id}][{$added}][{$queue->ip}][{$queue->site}]");
            $this->line("[{$connection}][{$row->id}][{$deleted}][{$queue->id}][{$added}][{$queue->ip}][{$queue->site}]");
        }

        return $this;
    }

    /**
     * Создание очереди
     * 
     * @param object $row Экземпляр модели заявки
     * @return \App\Models\RequestsQueue
     */
    public function createQueue($row)
    {
        $data = [
            'phone' => $row->number,
            'client_name' => $row->name,
            'comment' => $row->comment,
            'site' => idn_to_utf8($row->site),
            'page' => $row->page,
            'utm_source' => $row->utm_source,
            'utm_medium' => $row->utm_medium,
            'utm_campaign' => $row->utm_campaign,
            'utm_content' => $row->utm_content,
            'utm_term' => $row->utm_term,
            'device' => $row->device,
            'region' => $row->region,
        ];

        $hash = "";

        foreach ($data as $key => $value)
            $hash .= (string) $key . (string) $value . ";";

        $request_data = (object) Controller::encrypt($data);

        $queue = RequestsQueue::create([
            'request_data' => $request_data,
            'ip' => $row->ip,
            'site' => idn_to_utf8($row->site),
            'user_agent' => $row->user_agent ?? null,
            'created_at' => Carbon::createFromTimeString(
                $row->created_at ?? now()->format("Y-m-d H:i:s"),
                3
            ),
            'hash' => md5($hash),
        ]);

        return $queue;
    }

    /**
     * Добавление конфигурации подключений
     * 
     * @return mixed $this
     */
    public function setDatabasesConfigurations()
    {
        foreach ($this->databases as $db) {
            Config::set("database.connections.mysql_queue_{$db['id']}", [
                'driver' => 'mysql',
                'host' => $db['host'] ?? '127.0.0.1',
                'port' => $db['port'] ?? '3306',
                'database' => $db['database'] ?? 'forge',
                'username' => $db['user'] ?? 'forge',
                'password' => $db['password'] ?? '',
                'unix_socket' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_general_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
                'options' => extension_loaded('pdo_mysql') ? array_filter([
                    \PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                    \PDO::ATTR_TIMEOUT => 5,
                ]) : [],
            ]);
        }

        return $this;
    }

    /**
     * Проверка настроек ресурсов для автоматичесого доавбления заявки
     * 
     * @param string $resource
     * @return boolean
     */
    public function checkAutoDoneResource($resource)
    {
        foreach ($this->auto_sources as $row) {
            if ($row['site'] == $resource)
                return $row['setting'];
        }

        $setting = Queues::checkSourceForAutoDone($resource);

        $this->auto_sources[] = [
            'site' => $resource,
            'setting' => $setting,
        ];

        return $setting;
    }
}
