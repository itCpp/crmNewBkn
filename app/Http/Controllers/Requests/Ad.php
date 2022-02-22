<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Models\RequestsRow;
use Illuminate\Http\Request;

class Ad extends Controller
{
    /**
     * Данные для модального окна обращений по рекламе
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        if (!$row = RequestsRow::find($request->id))
            return response()->json(['message' => "Заявка не найдена"], 400);

        $row->clients = Requests::getClientPhones($row, $request->user()->can('clients_show_phone'));

        return response()->json([
            'row' => $row,
        ]);
    }
}
