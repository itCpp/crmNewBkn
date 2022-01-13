<?php

namespace App\Http\Controllers\Ratings\Data;

use App\Models\Base\CrmAgreement;

trait Cashbox
{
    /**
     * Поиск суммы договоров, заключенных по приходам операторов колл-центра
     * 
     * @return $this
     */
    public function getCashboxData()
    {
        $rows = CrmAgreement::selectRaw(
            'SUM(crm_kassa.uppSumma) as sum, crm_agreement.coll, crm_kassa.hidePin, crm_kassa.date'
        )
            ->join('crm_kassa', 'crm_kassa.nomerDogovora', '=', 'crm_agreement.nomerDogovora')
            ->where([
                ['crm_kassa.uppSumma', '!=', ""],
                ['crm_kassa.nomerDogovora', '!=', ""],
            ])
            ->whereBetween('crm_kassa.date', [
                $this->dates->start,
                $this->dates->stop,
            ])
            ->where('crm_agreement.coll', '!=', '')
            ->where('crm_agreement.coll', '!=', null)
            ->whereNotIn('crm_agreement.coll', [
                'Вторичка',
                'Улица',
                'Промо',
                'СМИ',
                'Иное',
            ])
            ->groupBy([
                'crm_agreement.coll',
                'crm_kassa.hidePin',
                'crm_kassa.date'
            ])
            ->get();

        $cahsbox = [];

        foreach ($rows as $row) {

            $row->pin = (int) ($row->coll ?: $row->hidePin);

            if (!$row->pin)
                continue;

            if (!in_array($row->pin, $this->data->pins))
                $this->data->pins[] = $row->pin;

            if (empty($cahsbox[$row->pin])) {
                $cahsbox[$row->pin] = [
                    'sum' => 0,
                    'pin' => $row->pin,
                    'dates' => [],
                ];
            }

            if (empty($cahsbox[$row->pin]['dates'][$row->date])) {
                $cahsbox[$row->pin]['dates'][$row->date] = 0;
            }

            $cahsbox[$row->pin]['sum'] += $row->sum;
            $cahsbox[$row->pin]['dates'][$row->date] += $row->sum;
        }

        $this->data->cahsbox = $cahsbox;

        return $this;
    }
}
