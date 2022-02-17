<?php

namespace App\Http\Controllers\Ratings\Data;

use App\Models\CrmMka\CrmRequest;
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
        if (env('NEW_CRM_OFF', true))
            return $this->getRequestsOldCrm();

        $rows = RequestsRow::select(
            'pin',
            'created_at',
            'check_moscow',
            'region'
        )
            ->whereNotNull('pin')
            ->whereBetween('created_at', [
                $this->dates->start . " 00:00:00",
                $this->dates->stop . " 23:59:59"
            ])
            ->get();

        $requests = [];

        foreach ($rows as $row) {

            $date = date("Y-m-d", strtotime($row->created_at));

            if (!in_array($row->pin, $this->data->pins))
                $this->data->pins[] = $row->pin;

            if (empty($requests[$row->pin])) {
                $requests[$row->pin] = [
                    'all' => 0,
                    'moscow' => 0,
                    'pin' => $row->pin,
                    'dates' => [],
                ];
            }

            if (empty($requests[$row->pin]['dates'][$date])) {
                $requests[$row->pin]['dates'][$date] = [
                    'all' => 0,
                    'moscow' => 0,
                ];
            }

            $requests[$row->pin]['all']++;
            $requests[$row->pin]['dates'][$date]['all']++;

            if (
                $row->check_moscow == 'moscow'
                or $row->region == 'Неизвестно'
                or $row->region == ''
                or $row->region == NULL
            ) {
                $requests[$row->pin]['moscow']++;
                $requests[$row->pin]['dates'][$date]['moscow']++;
            }
        }

        $this->data->requests = $requests;

        return $this;
    }

    /**
     * Поиск заявок в старой ЦРМ до перехода на новую
     * 
     * @return $this
     */
    public function getRequestsOldCrm()
    {
        CrmRequest::where([
            ['del', '!=', 'hide'],
            ['noView', '0'],
            ['pin', '!=', ""],
        ])
            ->whereBetween('staticDate', [$this->dates->start, $this->dates->stop])
            ->whereNotIn('state', ['brak', 'promo', 'vtorich'])
            ->whereNotIn('type', ['Подарки от Худякова'])
            ->get()
            ->each(function ($row) use (&$requests) {

                if (!in_array($row->pin, $this->data->pins))
                    $this->data->pins[] = $row->pin;

                if (empty($requests[$row->pin])) {
                    $requests[$row->pin] = [
                        'all' => 0,
                        'moscow' => 0,
                        'pin' => $row->pin,
                        'dates' => [],
                    ];
                }

                if (empty($requests[$row->pin]['dates'][$row->staticDate])) {
                    $requests[$row->pin]['dates'][$row->staticDate] = [
                        'all' => 0,
                        'moscow' => 0,
                    ];
                }

                $requests[$row->pin]['all']++;
                $requests[$row->pin]['dates'][$row->staticDate]['all']++;

                if (
                    $row->checkMoscow == 'moscow'
                    or $row->region == 'Неизвестно'
                    or $row->region == ''
                    or $row->region == NULL
                ) {
                    $requests[$row->pin]['moscow']++;
                    $requests[$row->pin]['dates'][$row->staticDate]['moscow']++;
                }
            });

        $this->data->requests = $requests ?? [];

        return $this;
    }
}
