<?php

namespace App\Jobs;

use App\Http\Controllers\Requests\Events;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IncomingCallAsteriskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param int|null $call_id Идентификтаор события
     * @return void
     */
    public function __construct(
        public int|null $call_id = null,
    ) {
        //
    }

    /**
     * Execute the job.
     *
     * @return null|bool|int
     */
    public function handle()
    {
        return (new Events)->incomingCallAsterisk($this->call_id);
    }
}
