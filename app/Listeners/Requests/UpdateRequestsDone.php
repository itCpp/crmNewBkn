<?php

namespace App\Listeners\Requests;

use App\Events\UpdateRequestRow;
use App\Events\Requests\UpdateRequestEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateRequestsDone
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
     * @param  UpdateRequestEvent  $event
     * @return void
     */
    public function handle(UpdateRequestEvent $event)
    {
        // Отправка уведомления об изменении заявки по каналу широковещания
        $broadcast = broadcast(new UpdateRequestRow($event->row));
        
        // Отправка уведомления только другим
        if ($event->toOthers)
            $broadcast->toOthers();
    }
}
