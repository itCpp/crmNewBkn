<?php

namespace App\Http\Controllers\Gates;

use App\Http\Controllers\Controller;
use App\Models\Gate;
use Illuminate\Http\Request;

class Gates extends Controller
{
    /**
     * Список полученных шлюзов
     * 
     * @var \App\Models\Gate[]
     */
    protected $gates;

    /**
     * Создание экземпляра объекта
     * 
     * @param null|int $type
     * @return void
     */
    public function __construct($type = null)
    {
        if ($type == "sms_incomings")
            $this->gates = $this->getSmsIncomingGates();
        else
            $this->gates = $this->getAllGates();
    }

    /**
     * Вывод списка шлюзов
     * 
     * @return \App\Models\Gate[]
     */
    public function get()
    {
        return $this->getGatesData($this->gates);
    }

    /**
     * Вывод всех шлюзов
     * 
     * @return \App\Models\Gate[]
     */
    public function getAllGates()
    {
        return Gate::all();
    }

    /**
     * Вывод шлюзов для проверки входящих сообщений
     * 
     * @return \App\Models\Gate[]
     */
    public function getSmsIncomingGates()
    {
        return Gate::where('check_incoming_sms', 1)->get();
    }

    /**
     * Расшифровка и подготовка данных
     * 
     * @param \App\Models\Gate[]
     * @return \App\Models\Gate[]
     */
    public function getGatesData($gates)
    {
        foreach ($gates as &$gate) {

            if ($gate->ami_user)
                $gate->ami_user = $this->decrypt($gate->ami_user);

            if ($gate->ami_pass)
                $gate->ami_pass = $this->decrypt($gate->ami_pass);

            if ($gate->headers)
                $gate->headers = $this->decrypt($gate->headers);
        }

        return $gates;
    }

    /**
     * Преобразует объект заголовков в строку
     * 
     * @param null|object $header
     * @param array $keys
     * @return string
     */
    public static function getHeaderString($header = null, $keys = [])
    {
        if (gettype($header) != "object")
            return "";

        foreach ($keys as $key => $value) {
            $header->$key = $value;
        }

        $string = "";

        foreach ($header as $key => $value) {
            $string .= " {$key}={$value};";
        }

        return $string;
    }
}
