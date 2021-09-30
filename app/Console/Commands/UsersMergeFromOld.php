<?php

namespace App\Console\Commands;

use App\Http\Controllers\Users\UsersMerge;
use Illuminate\Console\Command;

class UsersMergeFromOld extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:usersmerge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Миграция пользователей из старой ЦРМ';

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

        $merge = new UsersMerge;
        $merge->start();

        return 0;
    }
}
