<?php

namespace App\Http\Controllers\Sip;

use App\Models\Incomings\SipTimeEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SipMain extends Controller
{
    /**
     * Массив экстеншенов
     * 
     * @var array
     */
    protected $data = [];

    /**
     * Первое событие
     * 
     * @var string
     */
    protected $first;

    /**
     * Первое событие
     * 
     * @var string
     */
    protected $last;

    /**
     * Вывод статистики звонков по внутренним номерам
     * 
     * @param \Illuminate\Http\Request
     * @return response
     */
    public function stats(Request $request)
    {
        SipTimeEvent::whereDate('event_at', now())
            ->orderBy('event_at')
            ->orderBy('extension')
            ->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    $this->rowData($row);
                }
            });

        $rows = [];
        $this->first_time = strtotime($this->first);
        $this->last_time = strtotime($this->last);
        $this->period = $this->last_time - $this->first_time;

        foreach ($this->data as $row) {
            
            foreach ($row['events'] as &$event) {
                $event = $this->getPercentEvent($event);
            }

            $rows[] = $row;
        }

        return response()->json([
            'first' => $this->first,
            'last' => $this->last,
            'events' => $rows,
        ]);
    }

    /**
     * Метод формирования данных одной строки
     * 
     * @param \App\Models\Incomings\SipTimeEvent $row
     * @return null
     */
    public function rowData($row)
    {
        if (!$this->first or $this->first > $row->event_at)
            $this->first = $row->event_at;

        if ($this->last < $row->event_at)
            $this->last = $row->event_at;

        if (!isset($this->data[$row->extension]))
            $this->data[$row->extension] = $this->rowTemplate($row);

        if (!$this->data[$row->extension]['eventFirst'])
            $this->data[$row->extension]['eventFirst'] = $row->event_at;

        $this->data[$row->extension]['eventLast'] = $row->event_at;

        $this->data[$row->extension]['status'] = $row->event_status;

        $this->data[$row->extension]['events'][] = [
            'status' => $row->event_status,
            'event_at' => $row->event_at,
            'event_time' => strtotime($row->event_at),
        ];

        return null;
    }

    /**
     * Шаблон строки
     * 
     * @param \App\Models\Incomings\SipTimeEvent $row
     * @return array
     */
    public function rowTemplate($row)
    {
        return [
            'extension' => $row->extension,
            'status' => null,
            'timeWorked' => 0,
            'timeFree' => 0,
            'eventFirst' => null,
            'eventLast' => null,
            'events' => [],
        ];
    }

    /**
     * Расчет сдвига блока события
     * 
     * @param array
     * @return array
     */
    public function getPercentEvent($event)
    {
        $start = $event['event_time'] - $this->first_time;
        $event['percent'] = round(($start * 100) / $this->period, 4);

        return $event;
    }
}
