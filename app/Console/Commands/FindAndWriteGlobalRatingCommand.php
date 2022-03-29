<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FindAndWriteGlobalRatingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'writerating';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Находит и записывает все данные по рейтингу в глобальную таблицу';

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
        return 0;
    }
}
