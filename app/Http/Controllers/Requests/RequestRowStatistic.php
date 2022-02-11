<?php

namespace App\Http\Controllers\Requests;

use App\Models\Base\CrmComing;
use App\Models\Base\Office;
use App\Models\RequestsClient;

class RequestRowStatistic
{
    /**
     * Создание экземпляра объекта
     * 
     * @param object $row Экземпляр объекта подготовленной строки заявки
     * @return void
     */
    public function __construct($row)
    {
        $this->row = $row;

        $this->offices = [];
    }

    /**
     * Сбор статистики по заявке
     * 
     * @param object $row Экземпляр объекта подготовленной строки заявки
     * @return array
     */
    static function get($row)
    {
        $data = new static($row);

        return [
            'coming' => array_merge($data->getComingInfo(), $data->getAllComings()),
        ];
    }

    /**
     * Поиск прихода клиента
     * 
     * @return array
     */
    public function getComingInfo()
    {
        if (!$coming = CrmComing::where('unicIdClient', $this->row->id)->first())
            return [];

        return $this->serializeComingRow($coming);
    }

    /**
     * Обработка прихода
     * 
     * @param \App\Models\Base\CrmComing $row
     * @return array
     */
    public function serializeComingRow(CrmComing $row)
    {
        $time = explode("-", $row->time);
        $start = false;

        if (isset($time[0])) {
            if ($time[0] != "")
                $start = trim($time[0]);
        }

        $stop = isset($time[1]) ? trim($time[1]) : false;

        $start = $start ? date("H:i", strtotime("2020-01-01 {$start}")) : false;
        $stop = $stop ? date("H:i", strtotime("2020-01-01 {$stop}")) : false;

        return [
            'company' =>  $this->findOffice($row->company),
            'date' => $row->date,
            'pin' => $row->collPin,
            'start' => $start,
            'stop' => $stop,
            'time' => $row->time,
        ];
    }

    /**
     * Поиск информации по офису
     * 
     * @param string $old_id
     * @return array
     */
    public function findOffice($old_id)
    {
        if (isset($this->offices[$old_id]))
            return $this->offices[$old_id];

        if ($row = Office::where('oldId', $old_id)->first()) {
            $office = [
                'name' => $row->name,
                'icon' => $row->icon,
            ];
        }

        return $this->offices[$old_id] = ($office ?? null);
    }

    /**
     * Поиск всех возможных приходов клиента
     * 
     * @return array
     */
    public function getAllComings()
    {
        foreach ($this->row->clients ?? [] as $client)
            $clients[] = $client->id;

        if (!count($clients ?? []))
            return [];

        RequestsClient::whereIn('id', $clients)
            ->get()
            ->each(function ($row) use (&$requests) {
                foreach ($row->requests()->select('id')->get() as $request) {
                    $requests[] = $request->id;
                }
            });

        if (!count($requests ?? []))
            return [];

        $comings = CrmComing::select('unicIdClient', 'company', 'collPin', 'time', 'date')
            ->whereIn('unicIdClient', $requests)
            // ->where('unicIdClient', '!=', $this->row->id)
            ->get()
            ->map(function ($row) {
                return $this->serializeComingRow($row);
            })
            ->toArray();

        return [
            'comings' => $comings,
            'count' => count($comings),
        ];
    }
}
