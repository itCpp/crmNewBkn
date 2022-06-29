<?php

namespace App\Console\Commands;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dev\RequestsMerge;
use App\Models\CrmMka\CrmRequestsSetOwnPin;
use App\Models\RequestsRow;
use App\Models\RequestsStoryOwnPin;
use Illuminate\Console\Command;

class OldRequestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'old:requests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Old requests merge';

    /**
     * Экземпляр класса обработки данных
     * 
     * @var RequestsMerge
     */
    protected $merge;

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
        $this->merge = new RequestsMerge;

        $this->line("Миграция старых заявок в новую базу");

        $start = microtime(1);
        $memory = memory_get_usage();

        $this->info("Найдено заявок: {$this->merge->count}");
        $this->info("Время начала: " . date("Y-m-d H:i:s", $start));
        $this->newLine();

        $bar = $this->output->createProgressBar($this->merge->count);
        $bar->start();

        $stop = false;

        while (!$stop) {

            $stop = $this->merge->step() ? false : true;

            $bar->advance();
        }

        $bar->finish();

        $this->newLine(1);

        $this->handleSetOwnPins();

        $this->newLine(1);

        $this->info("Время завершения: " . date("Y-m-d H:i:s"));
        $this->info("Время работы: " . Controller::secToStr((int) (microtime(1) - $start)));

        $memory = memory_get_usage() - $memory;

        // Конвертация результата в нормальные байты 
        $i = 0;

        while (floor($memory / 1024) > 0) {
            $i++;
            $memory /= 1024;
        }

        $name = ['байт', 'КБ', 'МБ', "ГБ", "ТБ"];
        $this->info("Использовано памяти: " . round($memory, 2) . " " . ($name[$i] ?? ""));

        return 0;
    }

    /**
     * Перенос истории присвоения заявок
     * 
     * @return null
     */
    public function handleSetOwnPins()
    {
        $this->info("Перенос истории присвоения заявок: ");

        $bar = $this->output->createProgressBar(CrmRequestsSetOwnPin::count());
        $bar->start();

        $stop = false;
        $last_id = 0;

        while (!$stop) {

            if ($row = CrmRequestsSetOwnPin::where('id', '>', $last_id)->first()) {

                $request = RequestsRow::find($row->id_request);

                RequestsStoryOwnPin::create([
                    'request_id' => $row->id_request,
                    'pin_before' => (int) $row->pin_before ?: null,
                    'pin_after' => $row->pin_add,
                    'is_moscow' => (bool) $row->is_moscow,
                    'date_create' => $row->date,
                    'date_uplift' => $row->date_static,
                    'status_id' => $request->status_id ?? null,
                    'request_row' => $request ? $request->toArray() : [
                        'region' => $row->region,
                    ],
                    'created_at' => $row->created_at,
                    'updated_at' => $row->created_at,
                ]);

                $last_id = $row->id;
            }

            $bar->advance();
            $stop = !((bool) $row);
        }

        $bar->finish();
    }
}
