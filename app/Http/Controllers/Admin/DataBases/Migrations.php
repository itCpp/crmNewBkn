<?php

namespace App\Http\Controllers\Admin\DataBases;

use App\Exceptions\ExceptionsJsonResponse;
use App\Http\Controllers\Admin\Databases;
use App\Http\Controllers\Controller;
use App\Models\SettingsQueuesDatabase;
use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Migrations extends Controller
{
    /**
     * Список всех миграций
     * 
     * @var array
     */
    static $migrations = [
        \App\Http\Controllers\Admin\DataBases\Migrations\CreateAutomaticBlocksTable::class,
        \App\Http\Controllers\Admin\DataBases\Migrations\CreateBlockConfigsTable::class,
        \App\Http\Controllers\Admin\DataBases\Migrations\CreateBlocksTable::class,
        \App\Http\Controllers\Admin\DataBases\Migrations\CreateStatisticsTable::class,
        \App\Http\Controllers\Admin\DataBases\Migrations\CreateVisitsTable::class,
        \App\Http\Controllers\Admin\DataBases\Migrations\CreateCounterTrigger::class,
    ];

    /**
     * Инициализация объекта
     * 
     * @return void
     * 
     * @throws \App\Exceptions\ExceptionsJsonResponse
     */
    public function __construct(Request $request)
    {
        $this->request = $request;

        if ($this->row = SettingsQueuesDatabase::find($request->id))
            $this->setConfig();

        // throw new ExceptionsJsonResponse("Информация о базе данных не найдена");
    }

    /**
     * Применение конфигурации базы данных
     * 
     * @param null|SettingsQueuesDatabase $row
     * @return null
     */
    public function setConfig($row = null)
    {
        $row = $row ?: $this->row;

        Databases::setConfig([
            'id' => $row->id,
            'host' => $this->decrypt($row->host),
            'port' => $row->port ? $this->decrypt($row->port) : $row->port,
            'database' => $this->decrypt($row->database),
            'user' => $this->decrypt($row->user),
            'password' => $row->password ? $this->decrypt($row->password) : $row->password,
        ]);

        $this->connection = Databases::getConnectionName($row->id);

        $this->database = DB::connection($this->connection);
    }

    /**
     * Миграция базы данных на сайт
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function migrate()
    {
        try {
            $this->checkMigrationTable();
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        $this->lastMigrate = $this->getLastIdMigration();
        $count = 0;

        foreach (self::$migrations as $migrate) {
            if ($this->setMigrations($migrate))
                $count++;
        }

        return response()->json([
            'message' => "Выполнено миграций: $count",
            'errors' => $this->migrations_errors ?? null,
        ]);
    }

    /**
     * Проверка таблицы миграции
     * 
     * @return array
     */
    public function checkMigrationTable()
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('migrations')) {
            $schema->create('migrations', function (Blueprint $table) {
                $table->increments('id');
                $table->string('migration');
                $table->integer('batch');
            });
        }

        return $this->migrated = $this->getMigrations($this->database);
    }

    /**
     * Получить последний идентификатор миграции
     * 
     * @return int
     */
    public function getLastIdMigration()
    {
        return $this->database->table('migrations')->max('batch') ?: 0;
    }

    /**
     * Получить миграции базы данных
     * 
     * @param \Illuminate\Database\MySqlConnection $database
     * @return array
     */
    public static function getMigrations($database)
    {
        return $database->table('migrations')
            ->get()
            ->map(function ($row) {
                return $row->migration;
            })
            ->toArray();
    }

    /**
     * Возвращает список миграций
     * 
     * @return array
     */
    public static function getMigrationsList()
    {
        return self::$migrations;
    }

    /**
     * Проведение миграции
     * 
     * @param string $migrate
     * @return boolean 
     */
    public function setMigrations($migrate)
    {
        if (in_array($migrate, $this->migrated))
            return false;

        try {
            (new $migrate($this->connection))->up();

            $this->database->table('migrations')->insert([
                'migration' => $migrate,
                'batch' => $this->lastMigrate + 1,
            ]);

            return true;
        } catch (Exception $e) {
            $this->migrations_errors[] = $e->getMessage();
        }

        return false;
    }
}
