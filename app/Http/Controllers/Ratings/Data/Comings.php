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
        $this->data->comings = CrmComing::selectRaw('COUNT(*) as count, collPin as pin')
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
            ])
            ->groupBy('collPin')
            ->get()
            ->map(function ($row) {

                if (!in_array($row->pin, $this->data->pins))
                    $this->data->pins[] = $row->pin;

                return (object) $row->toArray();
            });

        return $this;
    }
}
