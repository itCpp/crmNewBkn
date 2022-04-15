<?php

namespace App\Http\Controllers\Sip;

use App\Models\Incomings\SipTimeEvent;
use App\Models\Incomings\SipInternalExtension;
use App\Http\Controllers\Controller;
use App\Models\UsersSession;
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
        $sipTimeEvent = new SipTimeEvent;

        if ($request->last)
            $sipTimeEvent = $sipTimeEvent->where('event_at', '>', $request->last);

        if ($request->start)
            $this->first = $request->start;

        $sipTimeEvent->whereBetween('event_at', [
            now()->format("Y-m-d 09:00:00"),
            now()->toDateTimeString()
        ])
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

        $this->time = time();

        foreach ($this->data as $row) {

            foreach ($row['events'] as &$event) {
                $event = $this->getPercentEvent($event);
            }

            $rows[] = $row;
        }

        return response()->json([
            'first' => $this->first,
            'last' => $this->last,
            'stop' => date("Y-m-d H:i:s"),
            'events' => $rows,
            'period' => $this->time - $this->first_time,
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
     * Определение цвета события
     * 
     * @param string $type
     * @return string
     */
    public function getEventColor($type)
    {
        if ($type == "Start" or $type == "Answer")
            return "red";

        if ($type == "Hangup")
            return "green";

        return "grey";
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

        $event['start'] = $start;
        $event['percent'] = round(($start * 100) / $this->period, 4);
        $event['period'] = $this->time - $this->first_time;

        return $event;
    }

    /**
     * Вывод статистики по звонкам
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getTapeTimes(Request $request)
    {
        $this->getTableAuths($request->user()->id);

        return $this->getTapeRows();
    }

    /**
     * Поиск всех событий звонков по столу
     * 
     * @return array
     */
    public function getTapeRows()
    {
        $start = now()->format("Y-m-d H:i:s"); // Время первого события
        $stop = now()->format("Y-m-d 20:00:00"); // Окончание рабочего дня
        $last = now()->format("Y-m-d H:i:s"); // Время последнего события

        $rows = count($this->data['tables']) ? SipTimeEvent::where(function ($query) {

            foreach ($this->data['tables'] as $row) {

                if ($row->table == null)
                    continue;

                $query->orWhere([
                    ['extension', $row->table],
                    ['event_at', '>=', $row->start ?? now()],
                    ['event_at', '<=', $row->stop ?? now()]
                ]);
            }
        })
            ->get()
            ->map(function ($row) use (&$start, &$stop, &$last) {

                if ($start > $row->created_at)
                    $start = $row->created_at;

                if ($row->created_at > $stop)
                    $stop = $row->created_at;

                $last = $row->created_at;

                return (object) [
                    'color' => $this->getEventColor($row->event_status),
                    'created_at' => $row->event_at,
                    'event_type' => $row->event_status,
                ];
            })
            ->toArray()
            : [];

        $a = strtotime($start);
        $b = strtotime($stop);
        $l = time();
        $count = count($rows) - 1;

        foreach ($rows as $key => &$row) {

            $row->percent = ($b - $a) > 0
                ? ($row->timestamp - $a) * 100 / ($b - $a) : 0;

            $prev = $key - 1;

            if ($key > 0)
                $rows[$prev]->width = $row->percent - $rows[$prev]->percent;

            if ($key === $count) {

                $width = ($b - $a) > 0
                    ? ($l - $a) * 100 / ($b - $a) : 0;

                $row->width = $width > 0 ? $width - $row->percent : 0;
            }
        }

        return [
            'start' => $start,
            'stop' => $stop,
            'startTime' => $a,
            'stopTime' => $b,
            'time' => $l,
            'rows' => $rows,
        ];
    }

    /**
     * Поиск столов, за которыми авторизирован сотрудник
     * 
     * @param int $user_id
     * @return array
     */
    public function getTableAuths($user_id)
    {
        $this->data['tables'] = [];

        UsersSession::withTrashed()
            ->select('ip', 'created_at')
            ->where([
                ['user_id', $user_id],
                ['created_at', now()->startOfDay()]
            ])
            ->orderBy('created_at', "DESC")
            ->get()
            ->each(function ($row) use (&$ips) {

                $ips[] = $row->ip;

                $this->data['tables'][] = (object) [
                    'start' => $row->created_at,
                    'stop' => $row->deleted_at,
                    'ip' => $row->ip,
                ];
            });

        $addrs = [];

        SipInternalExtension::select('extension', 'internal_addr')
            ->whereIn('internal_addr', array_unique($ips ?? []))
            ->where('internal_addr', '!=', null)
            ->get()
            ->each(function ($row) use (&$addrs) {
                $addrs[$row->internal_addr] = $row->extension;
            });

        $last = null;

        foreach ($this->data['tables'] as &$row) {
            $row->table = $addrs[$row->ip] ?? null;

            if ($last)
                $row->stop = $last;

            $last = $row->start;
        }

        return $this->data['tables'];
    }
}
