<?php

namespace App\Jobs;

use App\Events\Requests\NewSmsEvent;
use App\Http\Controllers\Sms\Sms;
use App\Http\Controllers\Users\UserData;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNewSmsRequestsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public array $rows
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
        $this->sms = new Sms;
        $this->users = [];

        SmsMessage::whereIn('id', $this->rows)
            ->get()
            ->each(function ($row) {

                $this->sms->show_phone = true;

                $message = $this->sms->getRowSms($row);
                $phone = $message['phone'];

                $row->requests()
                    ->where('pin', '!=', null)
                    ->get()
                    ->each(function ($request) use ($message, $phone) {

                        $user = $this->getUserData($request->pin);
                        $show_phone = optional($user)->can('clients_show_phone') ?: false;

                        $message['phone'] = $this->sms->displayPhoneNumber($phone, $show_phone, 4);
                        $message['to_pin'] = $request->pin;
                        $message['to_request'] = $request->id;
                        $message['client_name'] = $request->client_name;

                        broadcast(new NewSmsEvent($message));
                    });
            });
    }

    /**
     * Поиск объекта сотрудника
     * 
     * @param  string|integer $pin
     * @return \App\Http\Controllers\Users\UserData
     */
    public function getUserData($pin)
    {
        if (isset($this->users[$pin]))
            return $this->users[$pin];

        if (!$user = User::where('pin', $pin)->first())
            return $this->users[$pin] = null;

        return $this->users[$pin] = new UserData($user);
    }
}
