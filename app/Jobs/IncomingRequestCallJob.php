<?php

namespace App\Jobs;

use App\Http\Controllers\Requests\Events;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IncomingRequestCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Экземпляр модели события
     * 
     * @var \App\Models\Incomings\IncomingCallRequest
     */
    public $row;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\Incomings\IncomingCallRequest
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
        $events = new Events;
        $events->callEvent($this->row);
    }
}
