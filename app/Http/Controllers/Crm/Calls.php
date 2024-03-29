<?php

namespace App\Http\Controllers\Crm;

use App\Exceptions\ExceptionsJsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Requests\AddRequest;
use App\Http\Controllers\Requests\Requests;
use App\Models\CallDetailRecord;
use App\Models\CrmMka\CrmRequest;
use App\Models\CrmMka\CrmUsersToken;
use App\Models\Incomings\SipInternalExtension;
use App\Models\RequestsRow;
use App\Models\UsersSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Calls extends Controller
{
    use CallsLog;

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $phones = $this->getPhonesHashs($this->getPhone($request));

        return response()->json([
            'rows' => $this->getCalls($phones),
        ]);
    }

    /**
     * Формирует список хэш номеров телефонов
     * 
     * @param  array $phones
     * @return array
     */
    public function getPhonesHashs($phones = [])
    {
        return collect($phones)->map(function ($row) {
            return AddRequest::getHashPhone($this->checkPhone($row) ?: $row);
        })->toArray();
    }

    /**
     * Выводит файлы аудиозаписей звонков
     * 
     * @param  array $phone_hashs
     * @return array
     */
    public function getCalls($phone_hashs = [])
    {
        return CallDetailRecord::whereIn('phone_hash', $phone_hashs)
            ->where('duration', '>', 0)
            ->orderBy('call_at', "DESC")
            ->get()
            ->map(function ($row) {

                $url = Str::finish(env('CALL_DETAIL_RECORDS_SERVER', 'http://localhost:8000'), '/');

                if (Str::startsWith($row->path, '/'))
                    $row->path = Str::replaceFirst('/', '', $row->path);

                $url .= $row->path;

                $type = (optional(request()->user())->can('clients_show_phone') and !request()->hidePhone) ? 2 : 5;
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
    }

    /**
     * Поиск номера телефона
     * 
     * @param  \Illumiante\Http\Request $request
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
     * @param  int $id
     * @return array
     */
    public function getPhoneFromRequest($id)
    {
        if (request()->checkFromOld)
            return $this->getPhoneFromRequestOldCrm($id);

        if (!$row = RequestsRow::find($id))
            throw new ExceptionsJsonResponse("Заявка не найдена", 400);

        return collect(Requests::getClientPhones($row, true))->map(function ($client) {
            return $client->phone;
        })->toArray();
    }

    /**
     * Поиск номеров для старой ЦРМ
     * 
     * @param  int $id
     * @return array
     */
    public function getPhoneFromRequestOldCrm($id)
    {
        if (!$row = CrmRequest::select('phone', 'secondPhone')->find($id))
            throw new ExceptionsJsonResponse("Заявка не найдена", 400);

        if ($phone = $this->checkPhone($row->phone))
            $phones[] = $phone;

        foreach (explode("|", $row->secondPhone) as $phone) {
            if ($phone = $this->checkPhone($phone))
                $phones[] = $phone;
        }

        return array_values(array_unique($phones ?? []));
    }

    /**
     * Поиск оператора в настоящий момент по внутреннему номеру
     * 
     * @param  string $extension
     * @return string|null
     */
    public static function getPinFromExtension($extension = "")
    {
        if (!$sip = SipInternalExtension::where('extension', $extension)->first())
            return null;

        if (!$sip->internal_addr)
            return null;

        if (env('NEW_CRM_OFF', true))
            return self::getPinFromExtensionOldCrm($sip->internal_addr);

        $session = UsersSession::where('ip', $sip->internal_addr)
            ->orderBy('id', "DESC")
            ->first();

        return $session->user_pin ?? null;
    }

    /**
     * Поиск оператора в старой ЦРМ
     * 
     * @param  string $ip
     * @return string|null
     */
    public static function getPinFromExtensionOldCrm($ip)
    {
        $session = CrmUsersToken::where('ip', $ip)
            ->orderBy('id', "DESC")
            ->first();

        return $session->pin ?? null;
    }

    /**
     * Вывод списка фильтра журнала звонков
     * 
     * @return array
     */
    public function getFilterList()
    {
        $operators = CallDetailRecord::select('extension')
            ->where('extension', '!=', null)
            ->where('duration', '>', 0)
            ->distinct()
            ->get()
            ->map(function ($row) {
                return $row->extension;
            })
            ->toArray();

        $extension = CallDetailRecord::select('operator')
            ->where('operator', '!=', null)
            ->where('duration', '>', 0)
            ->distinct()
            ->get()
            ->map(function ($row) {
                return $row->operator;
            })
            ->toArray();

        return collect([...$extension, ...$operators])->unique()->sort()->values()->all();
    }
}
