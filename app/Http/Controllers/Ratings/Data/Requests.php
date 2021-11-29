<?php

namespace App\Http\Controllers\Ratings\Data;

use App\Models\RequestsRow;

trait Requests
{
    /**
     * Поиск заявок
     * 
     * @return $this
     */
    public function getRequests()
    {
        $this->data->requests = RequestsRow::selectRaw('COUNT(*) as count, pin')
            ->whereBetween('created_at', [
                $this->dates->start . " 00:00:00",
                $this->dates->stop . " 23:59:59"
            ])
            ->whereNotNull('pin')
            ->groupBy('pin')
            ->get()
            ->map(function ($row) {

                if (!in_array($row->pin, $this->data->pins))
                    $this->data->pins[] = $row->pin;

                return (object) $row->toArray();
            });

        return $this;
    }
}
