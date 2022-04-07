<?php

namespace App\Http\Controllers\Ratings\Data;

use App\Models\Fine;

trait Fines
{
    /**
     * Ищет штрафы сотрудников, назначенные в ЦРМ
     * 
     * @return $this
     */
    public function getFines()
    {
        Fine::whereBetween('fine_date', [$this->dates->start, $this->dates->stop])
            ->get()
            ->each(function ($row) use (&$fines) {

                $date = $row->fine_date->format("Y-m-d");

                if (!in_array($row->user_pin, $this->data->pins))
                    $this->data->pins[] = $row->user_pin;

                if (!isset($fines[$row->user_pin])) {
                    $fines[$row->user_pin] = [
                        'sum' => 0,
                        'dates' => [],
                    ];
                }

                if (!isset($fines[$row->user_pin]['dates'][$date])) {
                    $fines[$row->user_pin]['dates'][$date] = 0;
                }

                $fines[$row->user_pin]['sum'] += $row->fine;
                $fines[$row->user_pin]['dates'][$date] += $row->fine;
            });

        $this->data->fines = $fines ?? [];

        return $this;
    }
}
