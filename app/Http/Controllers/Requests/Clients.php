<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Models\RequestsClient;
use App\Models\RequestsRow;
use App\Models\RequestsStory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class Clients extends Controller
{
    /**
     * Добавление номера телефона в заявку
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function addClientPhone(Request $request)
    {
        if (!$request->phone = parent::checkPhone($request->phone))
            return response()->json(['message' => "Неправильный номер телефона"], 400);

        if (!$request->row = RequestsRow::find($request->id))
            return response()->json(['message' => "Заявка не найдена или уже удалена"], 400);

        foreach (Requests::getClientPhones($request->row, true) as $row) {
            if ($row->phone == parent::checkPhone($request->phone, 3))
                return response()->json(['message' => "Указанный номер телефона уже имеется в заявке"], 400);
        }

        $client = RequestsClient::where('hash', AddRequest::getHashPhone($request->phone))->first();

        if (!$client) {
            return self::createAdnResponse($request);
        }

        // Поиск заявок у клиента с тем-же источником
        $requests = $client->requests()->where('source_id', $request->row->source_id)->get();

        if (count($requests) and !$request->select) {

            foreach ($requests as &$r) {
                $r->status;
            }

            return response()->json([
                'message' => "Найден клиент с заявками того-же источника",
                'warning' => true,
                'requests' => $requests,
            ]);
        }

        // Проверка выбранной заявки при наличии нескольких с одним источником
        if (count($requests) and $request->select and !RequestsRow::find($request->select)) {
            return response()->json(['message' => "Выбранная заявка не существует или удалена"], 400);
        }

        $select = $request->select ?? $request->row->id;

        // Удаление всех повторяющихся заявок с одинаковым истоником
        $for_delete = RequestsRow::whereIn('id', array_merge(
            [$request->row->id],
            $requests->map(fn($item) => $item->id)->toArray()
        ));

        foreach ($for_delete->get() as $row) {
            if ($select and $row->id != $select) {
                $row->delete();
                RequestsStory::write($request, $row);
            }
        }

        if (!$client->requests()->where('id', $select)->count())
            $client->requests()->attach($select);

        $clients = Requests::getClientPhones($request->row, $request->user()->can('clients_show_phone'));
        $request->row->clients;

        RequestsStory::write($request, $request->row);

        return response()->json([
            'message' => "Номер телефона добавлен в заявку",
            'clients' => $clients,
        ]);
    }

    /**
     * Создание новой записи с номером и вывод ответа
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function createAdnResponse(Request $request)
    {
        $client = RequestsClient::create([
            'phone' => Crypt::encryptString($request->phone),
            'hash' => AddRequest::getHashPhone($request->phone),
        ]);

        $client->requests()->attach($request->row->id);

        $clients = Requests::getClientPhones($request->row, $request->user()->can('clients_show_phone'));
        $request->row->clients;

        RequestsStory::write($request, $request->row);

        return response()->json([
            'message' => "Номер телефона добавлен в заявку",
            'clients' => $clients,
        ]);
    }
}
