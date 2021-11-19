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
     * Данные сообщения
     * 
     * @var int
     */
    public $row;

    /**
     * Create a new job instance.
     *
     * @param int $row
     * @return void
     */
    public function __construct($row)
    {
        $this->row = $row;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $worker = new SmsSends($this->row);
        $worker->start();

        // if (!$worker->start())
        //     return $this->fail();
    }
}
