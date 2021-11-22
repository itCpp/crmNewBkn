<?php

namespace App\Jobs;

use App\Http\Controllers\Callcenter\SmsSends;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Количество попыток выполнения задания.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Данные сообщения
     * 
     * @var int
     */
    public $row;

    /**
     * Идентификатор заявки
     * 
     * @var int
     */
    public $request_id;

    /**
     * Create a new job instance.
     *
     * @param int $row
     * @param int $request_id
     * @return void
     */
    public function __construct($row, $request_id)
    {
        $this->row = $row;
        $this->request_id = $request_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $worker = (new SmsSends($this->row, $this->request_id))->start();

        if (!$worker)
            return $this->release(30);
    }
}
