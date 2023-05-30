<?php

namespace App\Jobs;

use App\Events\Users\TelegramBinded;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BindUserTelegram implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  int $user_pin
     * @param  int $telegram_id
     * @return void
     */
    public function __construct(
        protected $user_pin,
        protected $telegram_id
    ) {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($user = User::wherePin($this->user_pin)->first()) {

            $user->telegram_id = $this->telegram_id;
            $user->save();

            broadcast(new TelegramBinded($user->pin, $user->telegram_id));

            Controller::logData(new Request([
                'user_pin' => $this->user_pin,
                'telegram_id' => $this->telegram_id
            ]), $user);
        }
    }
}
