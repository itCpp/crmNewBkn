<?php

namespace App\Console\Commands;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings;
use App\Http\Controllers\Requests\AddRequest;
use App\Models\Base\CallDetailRecord as BaseCallDetailRecord;
use App\Models\CallDetailRecord;
use Illuminate\Console\Command;

class CallDetailRecordsMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:cdr';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Перенос информации о звонках';

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
        Settings::set('CALL_DETAIL_RECORDS_SAVE', false);

        $count = BaseCallDetailRecord::count();

        $this->info('Перенос детализации звонков');
        $this->line("Обнаружено записей: <fg=green;options=bold>{$count}</>");

        $bar = $this->output->createProgressBar($count);

        $bar->start();

        BaseCallDetailRecord::chunk(100, function ($rows) use ($bar) {

            foreach ($rows as $row) {

                $this->handleStep($row);

                $bar->advance();
            }
        });

        $bar->finish();

        Settings::set('CALL_DETAIL_RECORDS_SAVE', true);

        $this->info("Перенос детализции завершен");

        $count = CallDetailRecord::count();
        $this->line("Создано записей: <fg=green;options=bold>{$count}</>");
        $this->line("Записи с нулевой длительностью разговора отброшены");
        $this->newLine();

        return 0;
    }

    /**
     * Шаг выполнения переноса информации
     * 
     * @param \App\Models\Base\CallDetailRecord $row
     * @return null
     */
    public function handleStep($row)
    {
        if (!$row->duration)
            return null;

        CallDetailRecord::create([
            'event_id' => $row->event_id,
            'phone' => Controller::encrypt($row->phone),
            'phone_hash' => AddRequest::getHashPhone($row->phone),
            'extension' => $row->extension,
            'path' => $row->path,
            'call_at' => $row->call_at,
            'type' => $row->type,
            'duration' => $row->duration,
        ]);

        return null;
    }
}
