<?php

namespace App\Http\Controllers\Statistics;

use App\Models\CrmMka\CrmRequest;
use Illuminate\Http\Request;

trait OperatorsOld
{
    /**
     * Статистистика по старой ЦРМ
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function operatorsOld(Request $requests)
    {
        $this->date = date("Y-m-d");

        $this->getRequestsOld()
            ->getComings()
            ->getRecordsOld()
            ->getComingsInDayOld()
            ->getRecordsDayOld()
            ->getRecordsInDayOld()
            ->getRecordsNextDayOld()
            ->getRecordsToDayOld()
            ->getNotRingingOld()
            ->getDrainOld()
            ->getTotals();

        return $this->operators->flatten()->sortBy([
            ['efficiency', 'desc'],
        ])->toArray();
    }

    /**
     * Подсчет активных заявок
     * 
     * @return $this
     */
    public function getRequestsOld()
    {
        CrmRequest::selectRaw('count(*) as count, pin')
            ->where('staticDate', $this->date)
            ->where([
                ['del', ''],
                ['noView', 0],
                ['pin', '!=', ""]
            ])
            ->whereNotIn('state', ['brak'])
            ->where(function ($sql) {
                $sql->where('checkMoscow', "moscow")
                    ->orWhere('checkMoscow', NULL)
                    ->orWhere('checkMoscow', "");
            })
            ->groupBy('pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->pin, 'requests', $row->count);
            });

        return $this;
    }

    /**
     * Подсчет приходов сотрудника, по записям, сделанным день в день
     * 
     * @return $this
     */
    public function getComingsInDayOld()
    {
        CrmRequest::selectRaw('COUNT(*) as count, pin')
            ->where([
                ['del', ''],
                ['noView', 0]
            ])
            ->where('staticDate', $this->date)
            ->where('rdate', $this->date)
            ->whereIn('state', ['prihod'])
            ->whereIn('pin', $this->operators->keys())
            ->groupBy('pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->pin, 'comingsInDay', $row->count);
            });

        return $this;
    }

    /**
     * Подсчет записей сотрудника на сегодня
     * 
     * @return $this
     */
    public function getRecordsOld()
    {
        CrmRequest::selectRaw('count(*) as count, pin')
            ->where('rdate', $this->date)
            ->whereIn('state', ['zapis', 'podtverjden'])
            ->where('pin', '!=', null)
            ->groupBy('pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->pin, 'records', $row->count);
            });

        return $this;
    }

    /**
     * Подсчет записей сотрудника, сделанных за сегодня
     * 
     * @return $this
     */
    public function getRecordsDayOld()
    {
        CrmRequest::selectRaw('COUNT(*) as count, pin')
            ->where([
                ['del', ''],
                ['noView', 0]
            ])
            ->where('staticDate', $this->date)
            ->whereIn('state', ['zapis', 'podtverjden'])
            ->whereIn('pin', $this->operators->keys())
            ->groupBy('pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->pin, 'recordsDay', $row->count);
            });

        return $this;
    }

    /**
     * Подсчет записей день в день
     * 
     * @return $this
     */
    public function getRecordsInDayOld()
    {
        CrmRequest::selectRaw('COUNT(*) as count, pin')
            ->where([
                ['del', ''],
                ['noView', 0]
            ])
            ->where('staticDate', $this->date)
            ->where('rdate', $this->date)
            ->whereIn('state', ['zapis', 'podtverjden'])
            ->whereIn('pin', $this->operators->keys())
            ->groupBy('pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->pin, 'recordsInDay', $row->count);
            });

        return $this;
    }

    /**
     * Подсчет записей на следующий день
     * 
     * @return $this
     */
    public function getRecordsNextDayOld()
    {
        CrmRequest::selectRaw('COUNT(*) as count, pin')
            ->where([
                ['del', ''],
                ['noView', 0]
            ])
            ->where('staticDate', $this->date)
            ->where('rdate', $this->now->copy()->addDay(1)->format("Y-m-d"))
            ->whereIn('state', ['zapis', 'podtverjden'])
            ->whereIn('pin', $this->operators->keys())
            ->groupBy('pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->pin, 'recordsNextDay', $row->count);
            });

        return $this;
    }

    /**
     * Подсчет записей на следующий день
     * 
     * @return $this
     */
    public function getRecordsToDayOld()
    {
        CrmRequest::selectRaw('COUNT(*) as count, pin')
            ->where([
                ['del', ''],
                ['noView', 0]
            ])
            ->where('rdate', $this->now->copy()->addDay(1)->format("Y-m-d"))
            ->whereIn('state', ['zapis', 'podtverjden'])
            ->whereIn('pin', $this->operators->keys())
            ->groupBy('pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->pin, 'recordsToDay', $row->count);
            });

        return $this;
    }

    /**
     * Подсчет недозвонов у сотрудника
     * 
     * @return $this
     */
    public function getNotRingingOld()
    {
        CrmRequest::selectRaw('COUNT(*) as count, pin')
            ->where([
                ['del', ''],
                ['noView', 0],
            ])
            ->where(function ($query) {
                $query->where('rdate', $this->now->copy()->format("Y-m-d"))
                    ->orWhere('staticDate', $this->now->copy()->format("Y-m-d"));
            })
            ->whereIn('state', ['nedozvon'])
            ->whereIn('pin', $this->operators->keys())
            ->groupBy('pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->pin, 'notRinging', $row->count);
            });

        return $this;
    }

    /**
     * Подсчет сливов сотрудника
     * 
     * @return $this
     */
    public function getDrainOld()
    {
        CrmRequest::selectRaw('COUNT(*) as count, pin')
            ->where([
                ['del', ''],
                ['noView', 0],
            ])
            ->where(function ($query) {
                $query->where('rdate', $this->now->copy()->format("Y-m-d"))
                    ->orWhere('staticDate', $this->now->copy()->format("Y-m-d"));
            })
            ->whereIn('state', ['sliv'])
            ->whereIn('pin', $this->operators->keys())
            ->groupBy('pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->pin, 'drain', $row->count);
            });

        return $this;
    }
}
