<?php

namespace App\Http\Controllers\Agreements;

use App\Models\Base\CrmAgreement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait RowsQuery
{
    /**
     * Формирование запроса на вывод договоров
     * 
     * @param \Illuminate\Http\Request $request
     */
    public function getRows(Request $request)
    {
        return CrmAgreement::select(
            'crm_agreement.id',
            'crm_agreement.nomerDogovora',
            'crm_agreement.predmetDogovora',
            'crm_agreement.phone',
            'crm_agreement.status',
            'crm_agreement.date',
            'crm_agreement.tematika',
            'crm_agreement.predstavRashod',
            'crm_agreement.rashodPoDogovory',
            'crm_agreement.avans',
            'crm_agreement.summa',
            'crm_agreement.ostatok',
            'crm_agreement.doplata',
            'crm_agreement.company',
            'crm_agreement.oristFio',
            'crm_agreement.predstavRashodJson',
            'crm_agreement.FullNameClient',
            'c.date as coming_date',
            'c.time as coming_time',
            'c.unicIdClient',
            'crm_clients_unical.phone as phones',
            'crm_agreement.phone'
        )
            ->join('crm_coming as c', 'c.id', '=', 'crm_agreement.synchronizationId')
            ->leftjoin('crm_clients_unical', 'crm_clients_unical.id', '=', 'crm_agreement.uniclClientId')
            ->when(in_array($request->type, ['neobr', 'good', 'check', 'bad', 'nocall', 'search', 'all']), function ($query) use ($request) {
                $query = $this->setTypeQuery($query, $request);
            })
            ->where('crm_agreement.company', '!=', 'СПР')
            ->paginate(20);
    }

    /**
     * Применение типа договоров к запросу
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function setTypeQuery(Builder $query, Request $request)
    {
        if ($request->type == "return") {
            return $query->where('styles', 'LIKE', '%ffff01%')
                ->orderBy('crm_agreement.id', 'DESC')
                ->orderBy('crm_agreement.nomerDogovora', 'DESC');
        }

        $query = $query->select(
            'crm_agreement.id',
            'crm_agreement.nomerDogovora',
            'crm_agreement.predmetDogovora',
            'crm_agreement.phone',
            'crm_agreement.status',
            'crm_agreement.date',
            'crm_agreement.tematika',
            'crm_agreement.predstavRashod',
            'crm_agreement.rashodPoDogovory',
            'crm_agreement.avans',
            'crm_agreement.summa',
            'crm_agreement.ostatok',
            'crm_agreement.doplata',
            'crm_agreement.company',
            'crm_agreement.oristFio',
            'crm_agreement.predstavRashodJson',
            'crm_agreement.FullNameClient',
            'c.date as coming_date',
            'c.time as coming_time',
            'c.unicIdClient',
            'crm_clients_unical.phone as phones',
            'crm_agreement.phone',
            'coll.status AS colStatus',
            'coll.comment',
            'coll.commentOkk',
            'coll.date AS collDate'
        )
            ->leftjoin('crm_dogovor_coll_center as coll', function ($join) {
                $join->on('coll.nomerDogovora', '=', 'crm_agreement.nomerDogovora')
                    ->where('coll.last', 1);
            })
            ->where([
                ['crm_agreement.styles', 'NOT LIKE', '%ff0000%'],
                ['crm_agreement.nomerDogovora', '!=', '-'],
                ['crm_agreement.vidUslugi', 'NOT LIKE', '%Юр. консультация%'],
                ['crm_agreement.arhiv', 'NOT LIKE', '%Архив%'],
            ])
            ->when($request->type == "neobr", function ($query) {
                $query = $query->where(function ($query) {
                    $query->where('coll.status', 0)
                        ->orWhere('coll.status', "");
                });
            })
            ->when($request->type == "good", function ($query) {
                $query = $query->where('coll.status', 1);
            })
            ->when($request->type == "check", function ($query) {
                $query = $query->where('coll.status', 2);
            })
            ->when($request->type == "bad", function ($query) {
                $query = $query->where('coll.status', 3);
            })
            ->when($request->type == "nocall", function ($query) {
                $query = $query->where('coll.status', 4);
            })
            ->when(($request->type == "search" and (bool) $request->search), function ($query) use ($request) {
            });

        if (in_array($request->type, ["all", "search"]) || $request->user()->can('clients_agree_all'))
            return $query->orderBy('crm_agreement.id', 'DESC')->orderBy('crm_agreement.nomerDogovora', 'DESC');

        return $query->where(function ($query) use ($request) {
            $query->where('c.collPin', $request->user()->pin)
                ->when((bool) $request->user()->old_pin, function ($query) use ($request) {
                    $query->orWhere('c.collPin', $request->user()->old_pin);
                });
        })->orderBy('coll.id');
    }
}
