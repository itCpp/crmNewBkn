<?php

namespace App\Http\Controllers\Admin\Blocks;

use App\Http\Controllers\Controller;
use App\Models\Company\BlockIdSite;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientId extends Controller
{
    /**
     * Вывод информации об IP для блокировки по ID
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ip(Request $request)
    {
        $row = BlockIdSite::firstOrNew(['ip' => $request->ip]);

        $row->checked = ($row->deleted_at === null and $row->client_id !== null);
        $row->client_id = $row->client_id ?: null;

        return response()->json([
            'row' => $row
        ]);
    }

    /**
     * Сохранение информации об IP для блокировки по ID
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ipSave(Request $request)
    {
        if (!$request->client_id and $request->manual_client_id)
            return response()->json(['message' => "Не указан идентификатор клиента"], 400);

        if (!$row = BlockIdSite::find($request->id))
            $row = new BlockIdSite;

        $row->ip = $request->ip;
        $row->client_id = $request->client_id;
        $row->deleted_at = $request->checked ? null : ($row->deleted_at ?: now());

        if (!$request->manual_client_id and !$row->client_id)
            $row->client_id = Str::orderedUuid();

        $row->save();

        $this->logData($request, $row);

        $row->checked = ($row->deleted_at === null and $row->client_id !== null);

        return response()->json([
            'row' => $row
        ]);
    }
}
