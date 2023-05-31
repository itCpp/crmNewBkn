<?php

namespace App\Observers;

use App\Jobs\MaillerJob;
use App\Models\RequestsRow;

class RequestsRowObserver
{
    /**
     * Handle the RequestsRow "created" event.
     *
     * @param  \App\Models\RequestsRow  $requestsRow
     * @return void
     */
    public function created(RequestsRow $requestsRow)
    {
        MaillerJob::dispatch(
            $requestsRow,
            $requestsRow->isDirty(),
            $requestsRow->getOriginal(),
            $requestsRow->getChanges()
        );
    }

    /**
     * Handle the RequestsRow "updated" event.
     *
     * @param  \App\Models\RequestsRow  $requestsRow
     * @return void
     */
    public function updated(RequestsRow $requestsRow)
    {
        MaillerJob::dispatch(
            $requestsRow,
            $requestsRow->isDirty(),
            $requestsRow->getOriginal(),
            $requestsRow->getChanges()
        );
    }

    /**
     * Handle the RequestsRow "deleted" event.
     *
     * @param  \App\Models\RequestsRow  $requestsRow
     * @return void
     */
    public function deleted(RequestsRow $requestsRow)
    {
        //
    }

    /**
     * Handle the RequestsRow "restored" event.
     *
     * @param  \App\Models\RequestsRow  $requestsRow
     * @return void
     */
    public function restored(RequestsRow $requestsRow)
    {
        //
    }

    /**
     * Handle the RequestsRow "force deleted" event.
     *
     * @param  \App\Models\RequestsRow  $requestsRow
     * @return void
     */
    public function forceDeleted(RequestsRow $requestsRow)
    {
        //
    }
}
