<?php

namespace App\Jobs\Developer;

use App\Models\IncomingCallsToSource;
use App\Models\Incomings\SourceExtensionsName;
use App\Models\RequestsSource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RequestsSourceChangeAbbrNameJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  \App\Models\RequestsSource $row
     * @return void
     */
    public function __construct(
        public RequestsSource $row
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
        $phones = [];

        foreach ($this->row->resources as $resource) {
            if ($resource->type == "phone") {
                $phones[] = $resource->val;
            }
        }

        IncomingCallsToSource::whereIn('phone', $phones)
            ->get()
            ->each(function ($row) {

                $ext = SourceExtensionsName::firstOrNew([
                    'extension' => $row->extension,
                ]);

                $ext->abbr_name = $this->row->abbr_name;
                $ext->save();
            });
    }
}
