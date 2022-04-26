<?php

namespace App\Http\Controllers\Callcenter;

use App\Exceptions\CrmException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Gates\GateBase64;
use App\Http\Controllers\Offices\Offices;
use App\Http\Controllers\Requests\Requests;
use App\Http\Controllers\Sms\Sms as SmsSms;
use App\Jobs\SendSmsJob;
use App\Models\Gate;
use App\Models\Office;
use App\Models\RequestsRow;
use App\Models\SmsMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Sms extends Controller
{
    /**
     * Вывод данных для отправки смс
     * 
     * @param Request $request
     * @return response
     */
    public static function getSmsData(Request $request)
    {
        if (!$row = RequestsRow::find($request->id))
            return response()->json(['message' => "Заявка не найдена или уже удалена"], 400);

        $phones = Requests::getClientPhones($row, $request->user()->can('clients_show_phone'));

        $message = self::getSmsTemplate($row);

        if (!$message and !$request->user()->can('requests_send_sms_no_limit'))
            $alert = "Шаблон сообщения не сформирован, доступ к отправке сообщений с собственным текстом ограничен";
        else if (!$message)
            $alert = "Шаблон сообщения не сформирован";

        if ($alert ?? null)
            return response()->json(['message' => $alert], 400);

        $request->row = $row;

        return response()->json([
            'phones' => $phones,
            'message' => $message,
            'request' => $row,
            'messages' => self::getMessages($row),
            'now' => date("Y-m-d H:i:s"),
            'permits' => $request->user()->getListPermits([
                'requests_send_sms',
                'requests_send_sms_no_limit',
            ]),
            'alert' => $alert ?? null,
        ]);
    }

    /**
     * Формирование шаблона смс
     * 
     * @param RequestsRow $row
     * @return string|null
     */
    public static function getSmsTemplate(RequestsRow $row)
    {
        if (!$office = Office::find($row->address))
            return null;

        if (!is_array($office->statuses))
            return null;

        if (!in_array($row->status_id, $office->statuses))
            return null;

        if (!$office->sms)
            return null;

        $variables = self::getVariablesForTamplate($row, [
            'tel' => self::getSecretaryPhoneNumber($office, $row->callcenter_sector),
        ]);

        $template = $office->sms;

        preg_match_all('/\${(.*?)\}/', $template, $matches);

        foreach ($matches[1] as $match) {
            $template = str_replace('${' . $match . '}', $variables[$match] ?? "*****", $template);
        }

        return $template;
    }

    /**
     * Формирование перемнных для шаблона смс
     * 
     * @param RequestsRow $row
     * @param array $props
     * @return array
     */
    public static function getVariablesForTamplate(RequestsRow $row, $props = [])
    {
        $data = $row->toArray();

        if ($row->event_at) {
            $time = strtotime($row->event_at);

            $data['date'] = date("d.m.Y", $time);
            $data['time'] = date("H:i", $time);
        }

        // Удаление пустых значений
        foreach ($data as $key => $value) {
            if ($value === null)
                unset($data[$key]);
        }

        return array_merge($data, $props);
    }

    /**
     * Определение номера секретаря
     * 
     * @param Office $office
     * @param int|null $gate
     * @return int|null
     */
    public static function getSecretaryPhoneNumber(Office $office, $sector = null)
    {
        $phone = Offices::getSettingValue(
            office: $office,
            type: "phone_secretary_for_sector",
            sector: $sector,
        );

        if (!$phone)
            $phone = $office->tel;

        return $phone ? parent::checkPhone($phone, 3) : null;
    }

    /**
     * Отправка смс сообщения
     * 
     * @param Request $request
     * @return response
     */
    public static function sendSms(Request $request)
    {
        if (!$row = RequestsRow::find($request->request_id))
            return response()->json(['message' => "Заявка не найдена или уже удалена"], 400);

        if (!$row->address)
            return response()->json(['message' => "В заявке не указан адрес записи"], 400);

        if (!$client = $row->clients()->where('id', $request->phone)->first()) {
            return response()->json([
                'message' => "Номер телефона не найден",
                'errors' => [
                    'phone' => true,
                ]
            ], 400);
        }

        $limit = $request->user()->can('requests_send_sms_no_limit');

        // Проверка ограничений
        if ($last = $row->sms()->where('direction', 'out')->orderBy('id', 'DESC')->first()) {
            if (strtotime($last->created_at) > (time() - 300) and !$limit)
                return response()->json(['message' => "Нельзя отправлять больше одного сообшения по заявке в течение 5 минут"], 400);
        }

        if (!$gate = self::getGateChannelForSend($row))
            return response()->json(['message' => "Источник для отправки смс не определен. Повторите попытку и, если ошибка повторится, то сообщите об этом администратору"], 400);

        $sms = SmsMessage::create([
            'message_id' => Str::uuid(),
            'gate' => $gate['gate'],
            'channel' => $gate['channel'],
            'created_pin' => $request->user()->pin,
            'phone' => $client->phone,
            'message' => (new GateBase64)->encode($request->message),
            'direction' => "out",
        ]);

        $sms->requests()->attach($row->id);

        dispatch(new SendSmsJob($sms, $row->id));

        $message = $sms->toArray();
        $message['message'] = $request->message;

        return response()->json([
            'message' => "Сообщение создано",
            'sms' => $message,
        ]);
    }

    /**
     * Определение источника отправки сообщения по заявке
     * 
     * @param RequestsRow $row
     * @return null|array
     */
    public static function getGateChannelForSend(RequestsRow $row)
    {
        if (!$office = Office::find($row->address))
            return null;

        $gate_default = Offices::getSettingValue(
            office: $office,
            type: "gate_default",
            value: "gate",
        );

        $gate_channel_default = Offices::getSettingValue(
            office: $office,
            type: "gate_default",
            value: "channel",
        );

        $gate_to_sector = Offices::getSettingValue(
            office: $office,
            type: "gate_for_sector",
            value: "gate",
            sector: $row->callcenter_sector,
        );

        $gate_channel_to_sector = Offices::getSettingValue(
            office: $office,
            type: "gate_for_sector",
            value: "channel",
            sector: $row->callcenter_sector,
        );

        $gate = $gate_to_sector ?: $gate_default;
        $channel = $gate_channel_to_sector ?: $gate_channel_default;

        if (!$gate or !$channel)
            return null;

        if (!$gate_row = Gate::find($gate))
            return null;

        return [
            'gate' => $gate_row->id,
            'channel' => $channel,
        ];
    }

    /**
     * Вывод списка СМС
     * 
     * @param RequestsRow|Request $request
     * @param array $where Массив условий запроса
     * @return array
     */
    public static function getMessages($request, $where = [])
    {
        if ($request instanceof RequestsRow) {
            $row = $request;
        } else if ($request instanceof Requests) {
            $row = $request->row;
        }

        if (!($row ?? null))
            throw new CrmException("Отсутствует экземпляр модели заявки");

        $messages = $row->sms()->orderBy('created_at', 'DESC');

        if ($where)
            $messages = $messages->where($where);

        return $messages->get()->map(function ($row) {
            $row->message = (new GateBase64)->decode($row->message);
            return $row;
        })->toArray();
    }

    /**
     * Проверка обвнолений сообщений
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function getSmsUpdates(Request $request)
    {
        if (!$request->row = RequestsRow::find($request->id))
            return response()->json(['message' => "Заявка не найдена или уже удалена"], 400);

        if ($request->time)
            $where[] = ['updated_at', '>', $request->time];

        return response()->json([
            'now' => now(),
            'messages' => self::getMessages($request, $where ?? []),
        ]);
    }

    /**
     * Выводит номер телефона клиента из смс
     * 
     * @param  \Illumiante\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSmsPhone(Request $request)
    {
        if (!$row = SmsMessage::find($request->id))
            return response()->json(['message' => "СМС не найдено"], 400);

        $sms = new SmsSms;
        $sms->show_phone = $request->user()->can('clients_show_phone');
        $row = $sms->getRowSms($row);

        return response()->json([
            'row' => $row,
            'id' => $request->id,
        ]);
    }
}
