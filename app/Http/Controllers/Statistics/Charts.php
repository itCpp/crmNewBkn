<?php

namespace App\Http\Controllers\Statistics;

use App\Models\Base\CrmComing;
use App\Models\RequestsStoryPin;
use Illuminate\Http\Request;

class Charts
{
    /**
     * Количество дней для грфиков
     * 
     * @var int
     */
    const DAYS = 30;

    /**
     * Создание экземпляра объекта
     * 
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(
        protected Request $request
    ) {
        $this->pin = $request->user()->pin;
        $this->oldPin = $request->user()->old_pin;

        $this->start = now()->subDays(self::DAYS)->startOfDay();
        $this->stop = now()->endOfDay();
    }

    /**
     * Сбор данных для графиков
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getCharts(Request $request)
    {
        return [
            'comings' => $this->getComings(),
            'requests' => $this->getRequests(),
        ];
    }

    /**
     * Добавлеет недостающие дни в график
     * 
     * @param array
     * @return array
     */
    public function setEmptyDay($data)
    {
        if (!count($data))
            return [];

        $days = [];

        foreach ($data as $row) {
            $days[$row['date']] = $row;
        }

        for ($i = 0; $i <= self::DAYS; $i++) {

            $day = $this->start->copy()->addDays($i)->format("Y-m-d");

            $rows[] = empty($days[$day])
                ? [
                    'count' => 0,
                    'date' => $day,
                ]
                : $days[$day];
        }

        return $rows ?? [];
    }

    /**
     * График приходов
     * 
     * @return array
     */
    public function getComings()
    {
        return $this->setEmptyDay(
            CrmComing::selectRaw('count(*) as count, date')
                ->whereBetween('date', [
                    $this->start->copy()->format("Y-m-d"),
                    $this->stop->copy()->format("Y-m-d")
                ])
                ->where(function ($query) {
                    $query->where('collPin', $this->pin)
                        ->orWhere('collPin', $this->oldPin);
                })
                ->groupBy('date')
                ->get()
                ->toArray()
        );
    }

    /**
     * График количества заявок
     * 
     * @return array
     */
    public function getRequests()
    {
        return $this->setEmptyDay(
            RequestsStoryPin::selectRaw('count(*) as count, date(created_at) as date')
                ->whereBetween('created_at', [$this->start, $this->stop])
                ->where('new_pin', $this->pin)
                ->groupBy('date')
                ->get()
                ->toArray()
        );
    }
}
