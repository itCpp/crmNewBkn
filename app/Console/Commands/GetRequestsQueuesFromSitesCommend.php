<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GetRequestsQueuesFromSitesCommend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'requests:getfromsite';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check requests queue from sites databases';

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
