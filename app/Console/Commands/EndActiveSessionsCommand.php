<?php

namespace App\Console\Commands;

use App\Events\Users\CloseSession;
use App\Models\UsersSession;
use Illuminate\Console\Command;

/**
 * Завершает все активные сессии
 * 
 * Рекомендуется запускать кроной ежедневно в конце рабочего дня
 * `55 23 * * * php /<DOCUMENT_ROOT>/artisan users:endsessions`
 */
class EndActiveSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:endsessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Завершает все активные сессии';

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
        UsersSession::where('created_at', '>', now()->startOfDay())
            ->get()
            ->each(function ($row) {
                $row->delete();
                broadcast(new CloseSession($row->user_id, $row->token));
            });

        return 0;
    }
}
