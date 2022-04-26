<?php

namespace App\Http\Controllers\Callcenter;

use App\Events\AppUserEvent;
use App\Events\NewSmsEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Gates\GateBase64;
use App\Models\Gate;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Http\Request;

class SmsSends extends Controller
{
    /**
     * Экземпляр можели сообщения
     * 
     * @var SmsMessage
     */
    public $sms;

    /**
     * Идентификатор заявки
     * 
     * @var int
     */
    public $request_id;

    /**
     * Код ответа
     * 
     * @var null|int
     */
    public $response_code = null;

    /**
     * Создание экземпляра объекта
     * 
     * @param int $row
     * @param int $request_id
     * @return void
     */
    public function __construct($row, $request_id)
    {
        $this->sms = $row;
        $this->request_id = $request_id;
    }

    /**
     * Обработка очреди
     * 
     * @return boolean
     */
    public function start()
    {
        $send = $this->sendRequest();

        if (!$send) {
            $alert = [
                'type' => "error",
                'title' => "СМС #{$this->request_id}",
                'description' => "Сообщение не отправлено по причине: " . ($this->sms->response->Message ?? "Неизвестная причина"),
                "icon" => "mail",
                "time" => 10000,
            ];
        } else {
            $alert = [
                'type' => "success",
                'title' => "СМС #{$this->request_id}",
                'description' => "Сообщение отправлено",
                "icon" => "mail"
            ];
        }

        $this->sendAlert($alert);

        return $send;
    }

    /**
     * Уведомление о неудачной отправки сообщения
     * 
     * @param array $alert
     * @return $this
     */
    public function sendAlert($alert = [])
    {
        if ($user = User::where('pin', $this->sms->created_pin)->first()) {
            broadcast(new AppUserEvent(id: $user->id, alert: $alert));
        }

        broadcast(new NewSmsEvent($this->sms));

        return $this;
    }

    /**
     * Отправка запроса
     * 
     * @return boolean
     */
    public function sendRequest()
    {
        if (!$this->sms or !$gate = $this->getGateData($this->sms->gate))
            return false;

        extract($gate);

        if (empty($address) or empty($login) or empty($password))
            return false;

        if (!$phone = $this->checkPhone($this->decrypt($this->sms->phone), false))
            return false;

        $message = (new GateBase64)->decode($this->sms->message);

        $url = "http://{$address}/cgi/WebCGI?1500101=account={$login}&password={$password}&port={$this->sms->channel}&destination=%2B{$phone}&content=" . urlencode($message);

        $response = $this->sendGateRequest($url);

        if (is_array($response)) {

            $sent = false;
            $this->sms->sent_at = now();

            if (in_array($response['Response'] ?? null, ["Error", "Failed"]) or $this->response_code != 200) {
                $this->sms->failed_at = $this->sms->sent_at;
            } else {
                $this->sms->failed_at = null;
                $sent = true;
            }

            $this->sms->response = (object) $response;
            $this->sms->save();

            return $sent;
        }

        return false;
    }

    /**
     * Поиск данных шлюза
     * 
     * @param int|null $id
     * @return array|null
     */
    public function getGateData($id = null)
    {
        if (!$gate = Gate::where('id', $id)->where('for_sms', 1)->first())
            return null;

        return [
            'address' => $gate->addr,
            'login' => $gate->ami_user ? $this->decrypt($gate->ami_user) : null,
            'password' => $gate->ami_pass ? $this->decrypt($gate->ami_pass) : null,
        ];
    }

    /**
     * Отправка сообщения через запрос на шлюз
     * 
     * @param string $url
     * @return array|false
     */
    public function sendGateRequest($url)
    {
        $useragent = "MKA CRM back / PHP (" . PHP_VERSION . ")";

        $options = [
            CURLOPT_RETURNTRANSFER  => true,        // return web page
            CURLOPT_HEADER          => false,       // do not return headers
            CURLOPT_FOLLOWLOCATION  => true,        // follow redirects
            CURLOPT_USERAGENT       => $useragent,  // who am i
            CURLOPT_AUTOREFERER     => true,        // set referer on redirect
            CURLOPT_MAXREDIRS       => 10,          // stop after 10 redirects
            CURLOPT_SSL_VERIFYPEER  => false,       // SSL verification not required
            CURLOPT_SSL_VERIFYHOST  => false,       // SSL verification not required
            CURLOPT_CONNECTTIMEOUT  => 10,          // timeout on connect (in seconds)
            CURLOPT_TIMEOUT         => 10,          // timeout on response (in seconds)
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        $this->response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data['ResponseCode'] = $this->response_code;
        $array = explode("\r\n", $response);

        if (is_array($array)) {
            foreach ($array as $row) {

                $row = str_replace("\n", " ", $row);
                $row = explode(": ", $row);

                if (is_array($row)) {
                    $key = isset($row[0]) ? trim($row[0]) : null;
                    $value = isset($row[1]) ? trim($row[1]) : null;

                    if ($key and $value)
                        $data[$key] = $value;
                }
            }
        }

        return $data;
    }
}
