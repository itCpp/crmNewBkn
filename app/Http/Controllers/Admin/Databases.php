<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\DataBases\Migrations;
use App\Http\Controllers\Controller;
use App\Models\SettingsQueuesDatabase;
use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Databases extends Controller
{
    /**
     * Стандартное наименование таблицы очереди
     * 
     * @var string
     */
    protected $table_name = "queue_requests";

    /**
     * Вывод всех баз данных
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $rows = SettingsQueuesDatabase::all()
            ->map(function ($row) {
                return $this->serializeRow($row);
            })
            ->toArray();

        return response()->json([
            'rows' => $rows,
        ]);
    }

    /**
     * Примениение настроек внешних баз данных
     * 
     * @param null|int $id
     * @return array
     */
    public static function setConfigs($id = null)
    {
        return collect(SettingsQueuesDatabase::getAllDecrypt($id))
            ->map(function ($row) {
                self::setConfig($row);
                return "mysql_check_connect_{$row['id']}";
            })
            ->toArray();
    }

    /**
     * Формирование наименования подключения
     * 
     * @param int $id
     * @return string
     */
    public static function getConnectionName($id)
    {
        return "mysql_check_connect_" . ($id ?? 0);
    }

    /**
     * Создание настроек подключения к базам данных
     * 
     * @param string $connection
     * @return null
     */
    public static function setConfig($config)
    {
        $connection = "mysql_check_connect_" . ($config['id'] ?? 0);

        Config::set("database.connections.{$connection}", [
            'site_domain' => $config['domain'] ?? null,
            'connection_id' => $config['id'] ?? null,
            'driver' => 'mysql',
            'host' => $config['host'] ?? '127.0.0.1',
            'port' => $config['port'] ?? '3306',
            'database' => $config['database'] ?? 'forge',
            'username' => $config['user'] ?? 'forge',
            'password' => $config['password'] ?? '',
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

        return null;
    }

    /**
     * Преобразование строки для вывода
     * 
     * @param \App\Models\SettingsQueuesDatabase $row
     * @return array
     */
    public function serializeRow(SettingsQueuesDatabase $row)
    {
        $row->host = $this->decrypt($row->host);
        $row->user = $this->decrypt($row->user);
        $row->database = $this->decrypt($row->database);
        $row->table_name = $this->decrypt($row->table_name);

        $row->connected = null;

        if ($row->active) {

            $this->setConfig([
                'id' => $row->id,
                'host' => $row->host,
                'port' => $row->port ? $this->decrypt($row->port) : $row->port,
                'database' => $row->database,
                'user' => $row->user,
                'password' => $row->password ? $this->decrypt($row->password) : $row->password,
            ]);

            $connection = "mysql_check_connect_" . $row->id;

            $check = $this->checkConnection($connection);
            $row->connected_error = $check['error'] ?? null;
        }

        return array_merge($row->toArray(), $check ?? []);
    }

    /**
     * Проверка подключения к базе данных
     * 
     * @param string $config
     * @return array
     */
    public function checkConnection($connection)
    {
        try {
            DB::connection($connection)->getPdo();

            if ($stats = $this->checkAvailabilityStats($connection)) {
                $stats_visits = $this->countAllVisits($connection);
            }

            return [
                'stats' => $stats,
                'stats_visits' => $stats_visits ?? null,
                'connected' => true
            ];
        } catch (Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Проверка наличия подключенной статистики на сайте
     * 
     * @param string $connection
     * @return bool
     */
    public function checkAvailabilityStats($connection)
    {
        try {
            return Schema::connection($connection)->hasTable("block_configs");
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Проверка наличия таблицы с очередями
     * 
     * @param string $connection
     * @param string|null $table
     * @return bool
     */
    public function checkQueueTable($connection, $table)
    {
        try {
            return Schema::connection($connection)->hasTable($table ?: $this->table_name);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Проверка наличия таблицы миграции
     * 
     * @param string $connection
     * @return bool
     */
    public function checkMigrationTable($connection)
    {
        try {
            return Schema::connection($connection)->hasTable("migrations");
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Количество всех посещений
     * 
     * @param string $connection
     * @return bool
     */
    public function countAllVisits($connection)
    {
        try {
            return DB::connection($connection)->table('visits')->count();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Вывод данных одной строки
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        if (!$row = SettingsQueuesDatabase::find($request->id))
            return response(['message' => "Настройки базы данных не найндены"], 400);

        $password = $row->password ? $this->decrypt($row->password) : null;
        $connection = $this->getConnectionName($row->id);

        $row = $this->serializeRow($row);

        $row['password'] = $password;
        $row['migration_update'] = $this->checkUpdateMigrations($row['id']);
        $row['migration_has'] = $this->checkMigrationTable($connection);
        $row['queue_table_has'] = $this->checkQueueTable($connection, $row['table_name']);

        return response()->json([
            'row' => $row,
        ]);
    }

    /**
     * Сохранение данных
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function set(Request $request)
    {
        $request->validate([
            'host' => "required|ip",
            'port' => "nullable|integer",
            'user' => "required",
            'password' => "required",
            'database' => "required",
            'table_name' => "nullable",
            // 'domain' => "nullable|active_url",
        ]);

        $domain = parse_url($request->domain);

        if ($domain['host'] ?? null)
            $url = idn_to_ascii($domain['host']);
        else if ($domain['path'] ?? null)
            $url = idn_to_ascii($domain['path']);

        if ($request->domain and !$url) {
            return response()->json([
                'message' => "Ошибка в домене",
                'errors' => [
                    'domain' => true,
                ],
            ], 422);
        }

        $row = SettingsQueuesDatabase::whereId($request->id)->firstOrNew();

        $table = $this->decrypt($row->table_name);

        $row->name = $request->name;
        $row->active = $request->active;
        $row->host = $this->encrypt($request->host);
        $row->port = $this->encrypt($request->port);
        $row->user = $this->encrypt($request->user);
        $row->password = $this->encrypt($request->password);
        $row->database = $this->encrypt($request->database);
        $row->table_name = $this->encrypt($request->table_name);
        $row->domain = idn_to_utf8($url ?? null) ?: null;

        $row->save();

        $this->logData($request, $row, true);

        $row = $this->serializeRow($row);

        if (!$request->id)
            $error = $this->createQueueTable($row);
        else if ($table != $row['table_name'])
            $error = $this->renameQueueTable($row['id'], $table, $row['table_name']);

        return response()->json([
            'row' => $row,
            'error' => $error ?? null,
        ]);
    }

    /**
     * Создание таблицы заявок
     * 
     * @param array $row
     * @return null|string
     */
    public function createQueueTable($row)
    {
        try {
            $connection = $this->getConnectionName($row['id']);
            $schema = Schema::connection($connection);
            $table = $row['table_name'] ?? $this->table_name;

            if (!$schema->hasTable($table)) {

                $schema->create($table, function (Blueprint $table) {
                    $table->id();
                    $table->string('number', 100)->nullable()->comment('Номер телефона');
                    $table->string('name')->nullable()->comment('Имя клиента');
                    $table->text('comment')->nullable()->comment('Комментарий');
                    $table->string('ip')->nullable()->comment('IP адрес клиента');
                    $table->string('site')->nullable()->comment('Сайт обращения');
                    $table->text('page')->nullable()->comment('Страница сайта обращения');
                    $table->text('user_agent')->nullable();
                    $table->string('utm_source')->nullable();
                    $table->string('utm_medium')->nullable();
                    $table->string('utm_campaign')->nullable();
                    $table->string('utm_content')->nullable();
                    $table->string('utm_term')->nullable();
                    $table->string('device')->nullable();
                    $table->string('region')->nullable();
                    $table->tinyInteger('locked')->default(0)->comment('Блокировка строки')->index();
                    $table->tinyInteger('brak')->default(0)->comment('Бракованная строка')->index();
                    $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                });
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return null;
    }

    /**
     * Смена наименования таблицы
     * 
     * @param string $id
     * @param string $from
     * @param string $to
     * @return null|string
     */
    public function renameQueueTable($id, $from, $to)
    {
        $from = $from ?: $this->table_name;
        $to = $to ?: $this->table_name;

        try {
            $connection = $this->getConnectionName($id);
            $schema = Schema::connection($connection);

            if ($schema->hasTable($to))
                return "Таблица с таким именем уже существует";

            if (!$schema->hasTable($from)) {

                $error = $this->createQueueTable([
                    'id' => $id,
                    'table_name' => $from,
                ]);

                if ($error)
                    return $error;
            }

            $schema->rename($from, $to);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Флаг новых обновлений в миграциях
     * 
     * @param int $id
     * @return bool
     */
    public function checkUpdateMigrations($id)
    {
        $connection = $this->getConnectionName($id);

        try {
            $migrated = Migrations::getMigrations(DB::connection($connection));
        } catch (Exception) {
            return false;
        }

        if (count(Migrations::getMigrationsList()) > count($migrated))
            return true;

        return false;
    }

    /**
     * Список сайтов, доступных для просмотра
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function sites()
    {
        $sites = [];

        SettingsQueuesDatabase::where('active', 1)
            ->get()
            ->map(function ($row) {
                return $this->serializeRow($row);
            })
            ->each(function ($row) use (&$sites) {

                if ($row['stats'] ?? null) {

                    $name = $row['domain'] ?: "Сайт #" . $row['id'];

                    $sites[] = [
                        'key' => $row['id'],
                        'value' => $row['id'],
                        'text' => $name,
                    ];
                }
            });

        return response()->json([
            'sites' => $sites,
        ]);
    }
}
