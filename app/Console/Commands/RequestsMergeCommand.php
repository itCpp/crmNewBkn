<?php

namespace App\Console\Commands;

use App\Http\Controllers\Dev\RequestsMerge;
use Illuminate\Console\Command;

class RequestsMergeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'requests:merge';

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

        $this->merge = new RequestsMerge;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->question("                                       ");
        $this->question("  Миграция старых заявок в новую базу  ");
        $this->question("                                       ");
        
        $this->newLine();

        $start = microtime(1);

        $this->info("Найдено заявок: {$this->merge->count}");
        $this->info("Время начала: " . date("Y-m-d H:i:s", $start));
        $this->newLine();       

        $bar = $this->output->createProgressBar($this->merge->count);
        $bar->start();

        $stop = false;

        while (!$stop) {

            $stop = $this->merge->step() ? false : true;

            $bar->advance();

            break;
        }
        
        $bar->finish();

        $this->newLine();     
        $this->info("Время завершения: " . date("Y-m-d H:i:s"));
        $this->newLine();     

        return 0;
    }
}
