<?php

namespace App\Http\Controllers\Ratings\Data;

use App\Models\Base\CrmAgreement;

trait Agreements
{
    /**
     * Подсчет количетсва договоров
     * 
     * @return $this
     */
    public function getAgreementsData()
    {
        CrmAgreement::select(
            'crm_agreement.coll',
            'crm_coming.collPin',
            'crm_coming.collPinSecondComing'
        )
            ->leftjoin('crm_coming', 'crm_coming.id', '=', 'crm_agreement.synchronizationId')
            ->whereBetween('crm_agreement.date', [$this->dates->start, $this->dates->stop])
            ->get()
            ->each(function ($row) use (&$agreements) {

                $pin = (int) $row->coll;
                $collPinSecondComing = (int) $row->collPinSecondComing;

                if (!$pin and $collPinSecondComing)
                    $pin = $collPinSecondComing;

                if (!isset($agreements[$pin])) {
                    $agreements[$pin] = [
                        'firsts' => 0,
                        'seconds' => 0,
                        'all' => 0,
                    ];
                }

                if (!(int) $row->coll)
                    $agreements[$pin]['seconds']++;
                else
                    $agreements[$pin]['firsts']++;

                $agreements[$pin]['all']++;
            });

        $this->data->agreements = $agreements ?? [];

        return $this;
    }
}
