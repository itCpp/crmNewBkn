<?php

namespace App\Http\Controllers\Crm;

use App\Exceptions\ExceptionsJsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Requests\AddRequest;
use App\Http\Controllers\Requests\Requests;
use App\Models\CallDetailRecord;
use App\Models\RequestsRow;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Calls extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $phones = collect($this->getPhone($request))->map(function ($row) {
            return AddRequest::getHashPhone($this->checkPhone($row) ?: $row);
        })->toArray();

        $rows = CallDetailRecord::whereIn('phone_hash', $phones)
            ->orderBy('call_at', "DESC")
            ->get()
            ->map(function ($row) use ($request) {

                $url = Str::finish(env('CALL_DETAIL_RECORDS_SERVER', 'http://localhost:8000'), '/');

                if (Str::startsWith($row->path, '/'))
                    $row->path = Str::replaceFirst('/', '', $row->path);

                $url .= $row->path;

                $type = $request->user()->can() ? 2 : 5;
                $phone = $this->decrypt($row->phone);

                return [
                    'id' => $row->id,
                    'call_at' => $row->call_at ?: $row->created_at,
                    'duration' => (int) $row->duration,
                    'phone' => $this->checkPhone($phone, $type) ?: $phone,
                    'extension' => $row->extension,
                    'url' => $url ?? null,
                    'type' => $row->type,
                ];
            })
            ->toArray();

        return response()->json([
            'rows' => $rows,
        ]);
    }

    /**
     * Поиск номера телефона
     * 
     * @param \Illumiante\Http\Request $request
     * @return array
     */
    public function getPhone(Request $request)
    {
        if ($id = $request->input('request'))
            $phone = $this->getPhoneFromRequest($id);

        return $phone ?? [];
    }

    /**
     * Поиск номера телефона в заявке
     * 
     * @param int $id
     * @return array
     */
    public function getPhoneFromRequest($id)
    {
        if (!$row = RequestsRow::find($id))
            throw new ExceptionsJsonResponse("Заявка не найдена", 400);

        return collect(Requests::getClientPhones($row, true))->map(function ($client) {
            return $client->phone;
        })->toArray();
    }
}
