<?php

namespace App\Http\Controllers\Ratings\Data;

use App\Models\Base\CrmComing;

trait Comings
{
    /**
     * Поиск приходов операторов коллцентра за указанный период
     * 
     * @return $this
     */
    public function getComings()
    {
        $rows = CrmComing::selectRaw('COUNT(*) as count, collPin as pin, date')
            ->whereBetween('date', [
                $this->dates->start,
                $this->dates->stop,
            ])
            ->where('collPin', '!=', '')
            ->where('collPin', '!=', null)
            ->whereNotIn('collPin', [
                'Вторичка',
                'Улица',
                'Промо',
                'СМИ',
                'Иное',
                'ЦПП',
            ])
            ->groupBy('collPin')
            ->groupBy('date')
            ->get();

        $comings = [];

        foreach ($rows as $row) {

            if (!in_array($row->pin, $this->data->pins))
                $this->data->pins[] = $row->pin;

            if (empty($comings[$row->pin])) {
                $comings[$row->pin] = [
                    'count' => 0,
                    'pin' => $row->pin,
                    'dates' => [],
                ];
            }

            if (empty($comings[$row->pin]['dates'][$row->date])) {
                $comings[$row->pin]['dates'][$row->date] = 0;
            }

            $comings[$row->pin]['count'] += $row->count;
            $comings[$row->pin]['dates'][$row->date] += $row->count;
            
        }

        $this->data->comings = $comings;

        return $this;
    }
}
