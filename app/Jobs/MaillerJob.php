<?php

namespace App\Jobs;

use App\Models\Mailler;
use App\Models\RequestsRow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MaillerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  \App\Models\RequestsRow  $requestsRow
     * @param  bool  $isDirty
     * @param  array  $original
     * @param  array  $changes
     * @return void
     */
    public function __construct(
        protected RequestsRow $requestsRow,
        protected bool $isDirty,
        protected array $original,
        protected array $changes,
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
        Mailler::whereIsActive(true)
            ->get()
            ->each(function ($mailler) {
                MaillerHandleJob::dispatch(
                    $mailler,
                    $this->requestsRow,
                    $this->isDirty,
                    $this->original,
                    $this->changes
                );
            });
    }
}
