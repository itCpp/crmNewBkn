<?php

namespace App\Listeners\Requests;

use App\Events\UpdateRequestRow;
use App\Events\UpdateRequestRowForPin;
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
        $toExclude = []; // Список сотрудников для отмены общего уведомления

        // Событие для добавления заявки сотруднику
        if (isset($event->row->newPin)) {
            if ($event->row->newPin == $event->row->pin) {
                $toExclude[] = (int) $event->row->newPin;
                broadcast(new UpdateRequestRowForPin($event->row, $event->row->newPin, false));
            }
        }

        // Событие для удаления заявки у сотрудника
        if (isset($event->row->oldPin)) {
            if ($event->row->oldPin != $event->row->pin) {
                $toExclude[] = (int) $event->row->oldPin;
                broadcast(new UpdateRequestRowForPin($event->row, $event->row->oldPin, true));
            }
        }

        // Отправка уведомления для всех об изменении заявки по каналу широковещания
        $broadcast = broadcast(new UpdateRequestRow($event->row, $toExclude));

        // Отправка уведомления только другим
        if ($event->toOthers)
            $broadcast->toOthers();
    }
}
