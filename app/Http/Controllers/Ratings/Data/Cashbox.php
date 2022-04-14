<?php

namespace App\Http\Controllers\Ratings\Data;

use App\Models\Base\CrmAgreement;
use App\Models\Base\CrmKassa;

trait Cashbox
{
    /**
     * Поиск суммы договоров, заключенных по приходам операторов колл-центра
     * 
     * @return $this
     */
    public function getCashboxData()
    {
        $cahsbox = [];

        $rows = CrmAgreement::selectRaw(
            'SUM(crm_kassa.uppSumma) as sum, crm_agreement.coll, crm_kassa.hidePin, crm_kassa.date'
        )
            ->join('crm_kassa', 'crm_kassa.nomerDogovora', '=', 'crm_agreement.nomerDogovora')
            ->where([
                ['crm_kassa.uppSumma', '!=', ""],
                ['crm_kassa.nomerDogovora', '!=', ""],
            ])
            ->whereBetween('crm_kassa.date', [
                $this->dates->startPeriod,
                $this->dates->stopPeriod,
            ])
            ->where('crm_agreement.coll', '!=', '')
            ->where('crm_agreement.coll', '!=', null)
            ->whereNotIn('crm_agreement.coll', [
                'Улица',
                'Промо',
                'СМИ',
                'Иное',
                'ЦПП',
            ])
            ->groupBy([
                'crm_agreement.coll',
                'crm_kassa.hidePin',
                'crm_kassa.date'
            ])
            ->get()
            ->each(function ($row) use (&$cahsbox) {

                $pin = (int) ($row->coll) ?: $row->hidePin;

                if (!$pin)
                    return;

                if (!in_array($pin, $this->data->pins))
                    $this->data->pins[] = $pin;

                if (empty($cahsbox[$pin])) {
                    $cahsbox[$pin] = [
                        'sum' => 0,
                        'pin' => $pin,
                        'dates' => [],
                    ];
                }

                if (empty($cahsbox[$pin]['dates'][$row->date])) {
                    $cahsbox[$pin]['dates'][$row->date] = 0;
                }

                $cahsbox[$pin]['sum'] += $row->sum;
                $cahsbox[$pin]['dates'][$row->date] += $row->sum;
            });

        $this->data->cahsbox = (object) [
            'sum' => $this->getCashboxSums(),
            'users' => $cahsbox
        ];

        return $this;
    }

    /**
     * Метод получения сумм кассы
     * 
     * @return object
     */
    public function getCashboxSums()
    {
        return (object) [
            'period' => $this->getCashboxSum($this->dates->startPeriod, $this->dates->stopPeriod),
            'month' => $this->getCashboxSum($this->dates->startMonth, $this->dates->stopMonth),
        ];
    }

    /**
     * Расчет кассы за указанный период
     * 
     * @param string $start
     * @param string $stop
     * @return int
     */
    public function getCashboxSum($start, $stop)
    {
        return CrmKassa::where([
            ['crm_kassa.fioKlienta', '!=', "Денег в начале дня"],
            ['crm_agreement.styles', 'NOT LIKE', "%ff0001%"],
            ['crm_agreement.styles', 'NOT LIKE', "%FF0001%"],
        ])
            ->leftjoin('crm_agreement', 'crm_agreement.nomerDogovora', '=', 'crm_kassa.nomerDogovora')
            ->where(function ($query) {
                $query->whereNotIn('crm_kassa.opertionType', ['1', '2', '3'])
                    ->orWhere('crm_kassa.opertionType', NULL);
            })
            ->whereBetween('crm_kassa.date', [$start, $stop])
            ->sum('crm_kassa.uppSumma');
    }
}
