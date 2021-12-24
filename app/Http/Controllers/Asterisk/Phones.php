<?php

namespace App\Http\Controllers\Asterisk;

use App\Http\Controllers\Controller;
use App\Models\RequestsClient;
use Illuminate\Http\Request;

class Phones extends Controller
{
    /**
     * Массив ip адресов для получения доступа к выполнению
     * метода getNumberFromId
     * 
     * @var array
     */
    public $accessIp = [
        '127.0.0.1',
        '192.168.0.11',
        '192.168.0.181',
        '192.168.0.183',
        '192.168.0.184',
        '192.168.4.208',
        '192.168.4.209',
    ];

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
    }

    /**
     * Метод выводит номер телефона клиента по идентификатору заявки
     * 
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function getNumberFromId(Request $request)
    {
        if (!in_array($request->ip(), $this->accessIp) or !$request->number)
            return $this->extension;

        // Разделение строки на id и порядковый номер
        // шаблон номера `+1234567s1234567`
        $number = explode("s", $request->number);

        $id = $number[0] ?? 0; // Идентификатор заявки
        $client = $number[1] ?? 0; // Идентификатор клиента

        if (!$row = RequestsClient::find($client))
            return $this->extension;

        $phone = $this->decrypt($row->phone);

        return $this->checkPhone($phone, 3) ?: $this->extension;
    }
}
