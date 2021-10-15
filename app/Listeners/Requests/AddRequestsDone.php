<?php

namespace App\Listeners\Requests;

use App\Events\CreatedNewRequest;
use App\Events\Requests\AddRequestEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AddRequestsDone
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  AddRequestEvent  $event
     * @return void
     */
    public function handle(AddRequestEvent $event)
    {
        broadcast(new CreatedNewRequest($event->row, $event->response));
    }
}
