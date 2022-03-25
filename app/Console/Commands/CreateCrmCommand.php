<?php

namespace App\Console\Commands;

use App\Console\MyOutput;
use App\Http\Controllers\Settings;
use App\Models\CrmMka\CrmRequest;
use App\Models\CrmMka\CrmUser;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateCrmCommand extends Command
{
    use MyOutput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'createcrm
                            {--users : Перенести данные сотрудников}
                            {--requests : Перенести заявки}
                            {--cdr : Перенести детализацию звоков}
                            {--story : Перенести историю изменения заявок}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обнуление базы и заполнение её данными из старой ЦРМ';

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
        $start = microtime(1);
        $this->uuid = Str::orderedUuid();

        if (!$this->questionnaire())
            return 0;

        /** Сохранение данных перед переносом */
        $this->call('data:dump', ['--name' => $this->uuid]);

        /** Обнуление базы данных */
        $this->call('migrate:fresh', ['--seeder' => "CreateCrm"]);

        /** Перенос сотрудников */
        if ($this->users_merge)
            $this->call('old:users');

        /** Восстановление ранее сохраненных данных */
        $this->call('data:restore', ['--name' => $this->uuid]);

        /** Перенос старых заявок */
        if ($this->requests_merge)
            $this->call('old:requests');

        /** Перенос детализации вызовов */
        if ($this->cdr_merge)
            $this->call('old:cdr');

        /** Отключение блокировки добавления новых заявок */
        Settings::set('DROP_ADD_REQUEST', false);
        /** Включение проверки СМС на шлюзах */
        Settings::set('CRONTAB_SMS_INCOMINGS_CHECK', true);
        /** Включение приёма детализции вызовов */
        Settings::set('CALL_DETAIL_RECORDS_SAVE', true);

        $time = microtime(1) - $start;

        $this->newLine(1);

        $this->line("Время начала переноса: <fg=green;options=bold>" . date("Y-m-d H:i:s", $start) . "</>");
        $this->line("Время окончания переноса: <fg=green;options=bold>" . date("Y-m-d H:i:s") . "</>");
        $this->line("Время работы: <fg=green;options=bold>" . date("H:i:s", $time) . "</>");

        $this->newLine(1);

        if ($this->story) {
        } else {
            $this->line("Теперь можно запустить перенос истории заявок:");
            $this->info("php artisan old:requestshistory");
        }

        return 0;
    }

    /**
     * Опросник
     * 
     * @return bool
     */
    public function questionnaire()
    {
        $this->title('Перенос ЦРМ');

        try {
            CrmRequest::count();
        } catch (Exception $e) {
            $this->error("База данных старой ЦРМ не доступна!\n");
            $this->error($e->getMessage());

            return false;
        }

        if (!env("NEW_CRM_OFF", true)) {
            $this->error("Использование старой ЦРМ отключено в .env файле, возможно команда переноса запущена случайно");
            $this->line("Чтобы запустить перенос ЦРМ определите переменную <fg=green;options=bold>NEW_CRM_OFF</> в .env со значением <fg=green;options=bold>true</>");

            return false;
        }

        $this->users_merge = $this->option('users');
        $this->requests_merge = $this->option('requests');
        $this->cdr_merge = $this->option('cdr');
        $this->story = $this->option('story');

        $this->line("При запуске этой комманды вся текущая база данных будет удалена");

        if ($database = config('database.connections.' . env('DB_CONNECTION', "mysql")))
            $this->line("Затрагиваемая база данных <fg=green;options=bold>{$database['database']}</> <bg=blue>{$database['username']}@{$database['host']}</>");

        if (!$this->confirm('Желаете продолжить?', true)) {
            $this->error(' Перенос ЦРМ отмене ');
            return false;
        }

        if (!$this->users_merge and $this->confirm('Перенести всех сотрудников?', true)) {
            $this->users_merge = true;
        }

        if (!$this->requests_merge and $this->confirm('Перенести все заявки?', true)) {
            $this->requests_merge = true;
        }

        if (!$this->cdr_merge and $this->confirm('Перенести детализацию вызовов?', true)) {
            $this->cdr_merge = true;
        }

        $this->line(" История изменения заявок может длиться несколько дней,\r\n запустить процесс переноса истории можно позже");

        if (!$this->story and $this->confirm('Перенести историю изменения заявок сейчас?', false)) {
            $this->story = true;
        }

        return true;
    }
}
