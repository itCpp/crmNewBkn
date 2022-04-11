<?php

namespace App\Http\Controllers\Asterisk;

use App\Http\Controllers\Agreements\RowsQuery;
use App\Http\Controllers\Controller;
use App\Models\RequestsRow;
use Illuminate\Http\Request;

class Phones extends Controller
{
    use RowsQuery;

    /**
     * Массив ip адресов для получения доступа к выполнению
     * метода getNumberFromId
     * 
     * @var array
     */
    public $accessIp = [];

    /**
     * Резервный номер телефона для дозвона в никуда
     * 
     * @var string
     */
    public $extension;

    /**
     * Создание экземпляра объекта
     * 
     * @return void
     */
    public function __construct()
    {
        $this->extension = env('ASTERISK_EMPTY_EXTENSION', "1100");

        $this->accessIp = $this->envExplode("ASTERISK_ACCESS_IPS");
    }

    /**
     * Метод выводит номер телефона клиента по идентификатору заявки
     * Номер делится на две составные части, при наличии разделителя `s` - номер
     * телефона, привязанный к заявке в ЦРМ, при наличии разделителя `d` - номер
     * в договоре
     * 
     * Пример номеров:
     * `+1234567s1234567`, `+15745d0`
     * 
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function getNumberFromId(Request $request)
    {
        if (!in_array($request->ip(), $this->accessIp) or !$request->number)
            return $this->extension;

        if (strripos($request->number, "s") === false)
            return $this->getNumberFromIdAgreement($request);

        $number = explode("s", $request->number);

        $id = $number[0] ?? 0; // Идентификатор заявки
        $client_id = (int) ($number[1] ?? 0); // Идентификатор клиента

        if (!$row = RequestsRow::find($id))
            return $this->extension;

        if (!$client = $row->clients()->where('id', $client_id)->first())
            return $this->extension;

        $phone = $this->decrypt($client->phone);

        return $this->checkPhone($phone, 3) ?: $this->extension;
    }

    /**
     * Возвращает номер телефона из договора
     * 
     * @param  \Illuminate\Http\Request $request
     * @return string
     */
    public function getNumberFromIdAgreement(Request $request)
    {
        if (strripos($request->number, "d") === false)
            return $this->extension;

        $number = explode("d", $request->number);

        $id = $number[0] ?? 0; // Идентификатор договора
        $phone_id = (int) ($number[1] ?? 0); // Порядковый номер телефона

        $phones = $this->getPhonesListFromAgreementRow((int) $id);
        $phone = $phones[$phone_id] ?? $this->extension;

        return $this->checkPhone($phone, 3) ?: $this->extension;
    }
}
