<?php

namespace App\Listeners\Requests;

use App\Events\Requests\CreatedNewRequest;
use App\Events\Requests\AddRequestEvent;
use App\Events\Requests\UpdateRequestEvent;
use App\Http\Controllers\Requests\Tabs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Прослушивание события создания новой заявки
 */
class AddRequestsListen
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
        $created = $event->response['created'] ?? null; # Флаг создания заявки

        // Появление новой заявки
        if ($created) {
            broadcast(new CreatedNewRequest($event->row, $event->response));
        }
        // Новое обращение по существующей заявке
        else {
            // broadcast(new UpdateRequestEvent($event->row));
        }
    }
}
