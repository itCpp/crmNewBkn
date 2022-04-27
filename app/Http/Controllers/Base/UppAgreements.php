<?php

namespace App\Http\Controllers\Base;

use App\Http\Controllers\Controller;
use App\Models\RequestsRow;
use App\Models\RequestsRowsConfirmedComment;
use Illuminate\Http\Request;

class UppAgreements extends Controller
{
    /**
     * Выводит данные с комментариями для карточки клиента
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCollComment(Request $request)
    {
        $rows = RequestsRow::select('id', 'comment', 'comment_urist as uristComment')
            ->whereIn('id', explode(",", $request->ids))
            ->get()
            ->map(function ($row) use ($request) {

                $row->feedback = null;
                $row->preferredTypeCommunication = false;

                $verno = RequestsRowsConfirmedComment::where('request_id', $row->id)
                    ->when((bool) $request->pin, function ($query) use ($request) {
                        $query->where(function ($query) use ($request) {
                            $query->where('confirm_pin', $request->pin)
                                ->orWhere('confirm_pin', null);
                        })->orderBy('confirm_pin', "DESC");
                    })
                    ->orderBy('id', 'DESC')
                    ->first();

                $row->verno = $verno ? (int) $verno->confirmed : null;

                return $row;
            })
            ->toArray();

        return response()->json($rows);
    }
}
