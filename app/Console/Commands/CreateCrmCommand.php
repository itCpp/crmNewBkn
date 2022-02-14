<?php

namespace App\Console\Commands;

use App\Console\MyOutput;
use App\Http\Controllers\Settings;
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
                            {--cdr : Перенести детализацию звоков}';

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

        /** Перенос старых заявок */
        if ($this->requests_merge)
            $this->call('old:requests');

        /** Восстановление ранее сохраненных данных */
        $this->call('data:restore', ['--name' => $this->uuid]);

        /** Перенос детализации вызовов */
        if ($this->cdr_merge)
            $this->call('data:cdr');

        /** Отключение блокировки добавления новых заявок */
        Settings::set('DROP_ADD_REQUEST', false);
        /** Включение проверки СМС на шлюзах */
        Settings::set('CRONTAB_SMS_INCOMINGS_CHECK', true);
        /** Включение приёма детализции вызовов */
        Settings::set('CALL_DETAIL_RECORDS_SAVE', true);

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

        $this->users_merge = $this->option('users');
        $this->requests_merge = $this->option('requests');
        $this->cdr_merge = $this->option('cdr');

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

        return true;
    }
}
