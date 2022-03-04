<?php

namespace App\Http\Controllers\Admin\DataBases\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCounterTrigger
{
    /**
     * Создание объекта миграции
     * 
     * @param false|string $connection
     * @return void
     */
    public function __construct(protected $connection = false)
    {
        $this->schema = Schema::connection($this->connection);
    }

    /**
     * Запуск миграции
     * 
     * @return null
     */
    public function up()
    {
        $database = config("database.connections.{$this->connections}.database");

        $count = DB::connection($this->connection)
            ->table('INFORMATION_SCHEMA.TRIGGERS')
            ->where('TRIGGER_SCHEMA', $database)
            ->where('TRIGGER_NAME', 'count_requests')
            ->count();

        if ($count)
            return null;

        DB::connection($this->connection)->unprepared("
            CREATE TRIGGER `count_requests` AFTER INSERT ON `queue_requests` FOR EACH ROW BEGIN

    SELECT `value` INTO @requests FROM `block_configs` WHERE `name` = 'COUNT_REQUESTS_TO_AUTO_BLOCK' LIMIT 1;

    IF EXISTS(SELECT * from `statistics` WHERE `ip` = NEW.ip AND `date` = CURDATE()) THEN

        UPDATE `statistics`
        SET `requests` = `requests` + 1
        WHERE ip = NEW.ip AND date = CURDATE()
        LIMIT 1;

    ELSE

        INSERT INTO `statistics` SET
        `ip` = NEW.ip,
        `date` = CURDATE(),
        `requests` = 1;

    END IF;

    SELECT `requests` INTO @count from `statistics` WHERE `ip` = NEW.ip AND `date` = CURDATE() LIMIT 1;

    IF NOT EXISTS(SELECT * FROM `automatic_blocks` WHERE `ip` = NEW.ip AND `date` = CURDATE()) THEN
        IF (@count >= @requests) THEN
            INSERT INTO `automatic_blocks` SET
            `ip` = NEW.ip,
            `date` = CURDATE();
        END IF;
    END IF;

END
        ");
    }
}
