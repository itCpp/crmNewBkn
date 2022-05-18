<?php

namespace App\Console\Commands;

use App\Console\MyOutput;
use App\Http\Controllers\Dev\RequestsMerge;
use Illuminate\Console\Command;

class OldRequestsCommand extends Command
{
    use MyOutput;

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

        $this->title("Миграция старых заявок в новую базу");

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

        $this->newLine();
        $this->newLine();

        $this->info("Время завершения: " . date("Y-m-d H:i:s"));

        $stop = microtime(1) - $start;

        $this->info("Время работы: " . round($stop, 3) . " сек");

        $memory = memory_get_usage() - $memory;

        // Конвертация результата в килобайты и мегабайты 
        $i = 0;
        while (floor($memory / 1024) > 0) {
            $i++;
            $memory /= 1024;
        }

        $name = ['байт', 'КБ', 'МБ'];
        $this->info("Использовано памяти: " . round($memory, 2) . " " . ($name[$i] ?? ""));

        $this->newLine();

        return 0;
    }
}
