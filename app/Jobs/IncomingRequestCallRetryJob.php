<?php

namespace App\Jobs;

use App\Http\Controllers\Requests\Events;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IncomingRequestCallRetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Данные входящего звонка
     * 
     * @var \App\Models\IncomingCall
     */
    public $row;

    /**
     * Данные запроса
     * 
     * @var null|int
     */
    public $pin;

    /**
     * Данные запроса
     * 
     * @var null|int
     */
    public $ip;

    /**
     * Данные запроса
     * 
     * @var null|int
     */
    public $user_agent;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\IncomingCall $row
     * @param null|int $pin
     * @param null|string $ip
     * @param null|string $user_agent
     * @return void
     */
    public function __construct($row, $pin, $ip, $user_agent)
    {
        $this->row = $row;
        $this->pin = $pin;
        $this->ip = $ip;
        $this->user_agent = $user_agent;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Events::retryAddRequestFromCall($this->row, $this->pin, $this->ip, $this->user_agent);
    }
}
