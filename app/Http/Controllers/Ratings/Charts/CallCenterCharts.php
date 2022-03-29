<?php

namespace App\Http\Controllers\Ratings\Charts;

trait CallCenterCharts
{
    /**
     * Сбор данных для графиков
     * 
     * @return $this
     */
    public function getChartsData()
    {
        foreach ($this->data->users ?? [] as $user) {

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
}
