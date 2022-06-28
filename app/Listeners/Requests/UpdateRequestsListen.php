<?php

namespace App\Listeners\Requests;

use App\Events\Requests\UpdateRequestEvent;
use App\Events\Requests\UpdateRequestRow;
use App\Events\Requests\UpdateRequestRowForPin;
use App\Events\Requests\UpdateRequestRowForSector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateRequestsListen
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
        if (isset($event->row->oldSector) or isset($event->row->newSector))
            return $this->handleForChangeSector($event);

        $toExclude = []; // Список сотрудников для отмены общего уведомления

        // Событие для добавления заявки сотруднику
        if (isset($event->row->newPin)) {
            if ($event->row->newPin == $event->row->pin) {
                $toExclude[] = (int) $event->row->newPin;
                broadcast(new UpdateRequestRowForPin($event->row, $event->row->newPin, false, $event->row->toOwn ?? false));
            }
        }

        // Событие для удаления заявки у сотрудника
        if (isset($event->row->oldPin)) {
            if ($event->row->oldPin != $event->row->pin) {
                $toExclude[] = (int) $event->row->oldPin;
                broadcast(new UpdateRequestRowForPin($event->row, $event->row->oldPin, true));
            }
        }

        // Отправка уведомления для всех об изменении заявки
        $broadcast = broadcast(new UpdateRequestRow($event->row, $toExclude));

        // Отправка уведомления только другим
        if ($event->toOthers)
            $broadcast->toOthers();
    }

    /**
     * Обновление данных при смене сектора
     * 
     * @param  UpdateRequestEvent  $event
     */
    public function handleForChangeSector(UpdateRequestEvent $event)
    {
        $broadcast = broadcast(new UpdateRequestRowForSector($event->row));

        if ($event->toOthers)
            $broadcast->toOthers();

        return false;
    }
}
