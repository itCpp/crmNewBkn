<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SettingsQueuesDatabase;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Databases extends Controller
{
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

            $check = $this->checkConnection([
                'id' => $row->id,
                'host' => $row->host,
                'port' => $row->port ? $this->decrypt($row->port) : $row->port,
                'database' => $row->database,
                'user' => $row->user,
                'password' => $row->password ? $this->decrypt($row->password) : $row->password,
            ]);

            $row->connected = $check['connected'] ?? false;
            $row->stats = $check['stats'] ?? null;
            $row->connected_error = $check['error'] ?? null;
        }

        return $row->toArray();
    }

    /**
     * Проверка подключения к базе данных
     * 
     * @param array $config
     * @return array
     */
    public function checkConnection($config)
    {
        Config::set("database.connections.mysql_check_connect_{$config['id']}", [
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

        try {
            DB::connection("mysql_check_connect_{$config['id']}")->getPdo();

            return [
                'stats' => $this->checkAvailabilityStats("mysql_check_connect_{$config['id']}"),
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
        return Schema::connection($connection)->hasTable("block_configs");
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

        $row->host = $row->host ? $this->decrypt($row->host) : null;
        $row->port = $row->port ? $this->decrypt($row->port) : null;
        $row->user = $row->user ? $this->decrypt($row->user) : null;
        $row->database = $row->database ? $this->decrypt($row->database) : null;
        $row->table_name = $row->table_name ? $this->decrypt($row->table_name) : null;
        $password = $row->password ? $this->decrypt($row->password) : null;

        $row = $row->toArray();

        $row['password'] = $password;

        return response()->json([
            'row' => $row,
        ]);
    }

    /**
     * Созранение данных
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
        ]);

        $row = SettingsQueuesDatabase::whereId($request->id)->firstOrNew();

        $row->name = $request->name;
        $row->active = $request->active;
        $row->host = $this->encrypt($request->host);
        $row->port = $this->encrypt($request->port);
        $row->user = $this->encrypt($request->user);
        $row->password = $this->encrypt($request->password);
        $row->database = $this->encrypt($request->database);
        $row->table_name = $this->encrypt($request->table_name);

        $row->save();

        $this->logData($request, $row, true);

        return response()->json([
            'row' => $this->serializeRow($row),
        ]);
    }
}
