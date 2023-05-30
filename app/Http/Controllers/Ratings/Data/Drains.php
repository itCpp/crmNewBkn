<?php

namespace App\Http\Controllers\Ratings\Data;

use App\Models\CrmMka\CrmRequest;
use App\Models\RequestsRow;

/**
 * Поиск информации о количестве сливов
 */
trait Drains
{
    /**
     * Поиск заявок
     * 
     * @return $this
     */
    public function getDrains()
    {
        if (env('NEW_CRM_OFF', true))
            return $this->getDrainsOldCrm();

        $status_ids = explode(",", env("STATISTICS_OPERATORS_STATUS_DRAIN_ID", ""));

        $rows = RequestsRow::select('pin', 'created_at')
            ->whereNotNull('pin')
            ->whereIn('status_id', $status_ids)
            ->whereBetween('created_at', [
                $this->dates->start . " 00:00:00",
                $this->dates->stop . " 23:59:59"
            ])
            ->get();

        $drains = [];

        foreach ($rows as $row) {

            $date = date("Y-m-d", strtotime($row->created_at));

            if (!in_array($row->pin, $this->data->pins))
                $this->data->pins[] = $row->pin;

            if (empty($drains[$row->pin])) {
                $drains[$row->pin] = [
                    'count' => 0,
                    'pin' => $row->pin,
                    'dates' => [],
                ];
            }

            if (empty($drains[$row->pin]['dates'][$date])) {
                $drains[$row->pin]['dates'][$date] = 0;
            }

            $drains[$row->pin]['count']++;
            $drains[$row->pin]['dates'][$date]++;
        }

        $this->data->drains = $drains;

        return $this;
    }

    /**
     * Поиск заявок в старой ЦРМ до перехода на новую
     * 
     * @return $this
     */
    public function getDrainsOldCrm()
    {
        CrmRequest::where([
            ['del', '!=', 'hide'],
            ['noView', '0'],
            ['pin', '!=', ""],
        ])
            ->whereBetween('rdate', [$this->dates->start, $this->dates->stop])
            ->whereIn('state', ['sliv'])
            ->get()
            ->each(function ($row) use (&$drains) {

                if (!in_array($row->pin, $this->data->pins))
                    $this->data->pins[] = $row->pin;

                if (empty($drains[$row->pin])) {
                    $drains[$row->pin] = [
                        'count' => 0,
                        'pin' => $row->pin,
                        'dates' => [],
                    ];
                }

                if (empty($drains[$row->pin]['dates'][$row->rdate])) {
                    $drains[$row->pin]['dates'][$row->rdate] = 0;
                }

                $drains[$row->pin]['count']++;
                $drains[$row->pin]['dates'][$row->rdate]++;
            });

        $this->data->drains = $drains ?? [];

        return $this;
    }
}
