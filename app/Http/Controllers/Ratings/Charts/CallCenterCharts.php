<?php

namespace App\Http\Controllers\Ratings\Charts;

use App\Http\Controllers\Dates;
use App\Models\RatingStory;
use Carbon\Carbon;

trait CallCenterCharts
{
    /**
     * Сбор данных для графиков
     * 
     * @return $this
     */
    public function getChartsData()
    {
        $this->getDataForMiniCharts();

        $day_stop = $this->dates_charts_mini->stop ?? now()->format("Y-m-d");

        foreach ($this->data->users ?? [] as &$user) {

            $user->charts_mini = $this->charts_mini[$user->pin] ?? [];

            $day_data = (object) [];

            foreach ($user->dates ?? [] as $day) {
                if ($day->date == $day_stop) {
                    $day_data = $day;
                    break;
                }
            }

            if (($this->append_to_day ?? null) and count($user->charts_mini)) {

                $appends = [
                    'requests' => $day_data->requestsAll ?? 0,
                    'requests_moscow' => $day_data->requests ?? 0,
                    'comings' => $day_data->comings ?? 0,
                    'drains' => $day_data->drains ?? 0,
                    'agreements_firsts' => $this->data->agreements[$user->pin]['dates'][$day_stop]['firsts'] ?? 0,
                    'agreements_seconds' => $this->data->agreements[$user->pin]['dates'][$day_stop]['firsts'] ?? 0,
                ];

                foreach ($appends as $key => $count) {
                    $user->charts_mini[$key][count($user->charts_mini[$key]) - 1] = $count;
                }
            }

            $data[] = [
                'fio' => $user->fio,
                'pin' => $user->pin,
                'efficiency' => $user->efficiency ?? 0,
                'efficiency_agreement' => $user->efficiency_agreement ?? 0,
                'comings' => $user->comings ?? 0,
                'agreements' => $user->agreements['firsts'] ?? 0,
            ];
        }

        $data = collect($data ?? [])
            ->sortBy('pin')
            ->values();

        foreach ($data->toArray() as $row) {

            $data = [
                'fio' => $row['fio'],
                'pin' => $row['pin'],
                'pin_fio' => $row['pin'] . " " . $row['fio'],
            ];

            if ((int) $row['efficiency'] > 0 and (int) $row['efficiency_agreement'] > 0) {

                $efficiency[] = array_merge($data, [
                    'value' => $row['efficiency'],
                    'type' => "КПД приходов",
                ]);

                $efficiency[] = array_merge($data, [
                    'value' => $row['efficiency_agreement'],
                    'type' => "КПД договоров",
                ]);

                $comings[] = array_merge($data, [
                    'count' => $row['comings'],
                    'name' => "Приходы",
                ]);

                $agreements[] = array_merge($data, [
                    'count' => $row['agreements'],
                    'name' => "Договоры",
                ]);
            }
        };

        $this->data->charts = [
            'efficiency' => $efficiency ?? [],
            'comings' => $comings ?? [],
            'agreements' => $agreements ?? [],
        ];

        return $this;
    }

    /**
     * Данные для мини грфиков
     * 
     * @return $this
     */
    public function getDataForMiniCharts()
    {
        if (!request()->toChats)
            return $this;

        if ($this->dates->stop >= $this->dates->day) {
            $start = Carbon::create($this->dates->day)->subDays(15);
            $stop = $this->dates->day;
        } else {
            $start = $this->dates->startPeriod;
            $stop = $this->dates->stopPeriod;
        }

        $this->dates_charts_mini = new Dates($start, $stop);

        RatingStory::whereIn('pin', $this->data->pin_list ?? [])
            ->whereBetween('to_day', [
                $this->dates_charts_mini->start,
                $this->dates_charts_mini->stop,
            ])
            ->orderBy('to_day', 'DESC')
            ->get()
            ->each(function ($row) use (&$charts) {

                $data = $row->rating_data;

                $charts[$row->pin][$row->to_day] = [
                    'requests' => $data->requestsAll ?? 0,
                    'requests_moscow' => $data->requests ?? 0,
                    'comings' => $data->comings ?? 0,
                    'agreements_firsts' => $data->agreements->firsts ?? 0,
                    'agreements_seconds' => $data->agreements->seconds ?? 0,
                    'drains' => $data->drains ?? 0,
                ];
            });

        foreach ($charts ?? [] as $pin => $data) {

            $this->charts_mini[$pin]['start'] = $this->dates_charts_mini->start;

            foreach ($this->dates_charts_mini->days as $day) {

                if ($day > $this->dates->day)
                    continue;

                /** Дополнить последний день графика текщими данными */
                if (!($this->append_to_day ?? null) and $day == $this->dates->day) {
                    $this->append_to_day = true;
                }

                $this->charts_mini[$pin]['requests'][] = $data[$day]['requests'] ?? 0;
                $this->charts_mini[$pin]['requests_moscow'][] = $data[$day]['requests_moscow'] ?? 0;
                $this->charts_mini[$pin]['comings'][] = $data[$day]['comings'] ?? 0;
                $this->charts_mini[$pin]['agreements_firsts'][] = $data[$day]['agreements_firsts'] ?? 0;
                $this->charts_mini[$pin]['agreements_seconds'][] = $data[$day]['agreements_seconds'] ?? 0;
                $this->charts_mini[$pin]['drains'][] = $data[$day]['drains'] ?? 0;
                $this->charts_mini[$pin]['stop'] = $day;
            }
        }

        $this->data->charts_mini = $this->charts_mini ?? [];

        return $this;
    }
}
