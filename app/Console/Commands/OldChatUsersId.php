<?php

namespace App\Console\Commands;

use App\Models\ChatRoom;
use App\Models\CrmMka\CrmUser;
use App\Models\User;
use Illuminate\Console\Command;

class OldChatUsersId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'old:chatusersid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Сменяет идентификтары пользователей в чате при переносе ЦРМ';

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
        $users_id = [];

        ChatRoom::lazy()->each(function ($row) use (&$users_id) {

            $users_id[] = $row->user_id;

            foreach (explode(",", $row->user_to_user) as $user_id) {
                $users_id[] = (int) $user_id;
            }
        });

        CrmUser::whereIn('id', array_values(array_unique($users_id)))
            ->get()
            ->each(function ($row) {

                $user = User::where('pin', $row->pin)->first();
            });

        return 0;
    }
}
