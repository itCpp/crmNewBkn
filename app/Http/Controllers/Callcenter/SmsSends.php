<?php

namespace App\Http\Controllers\Callcenter;

use App\Http\Controllers\Controller;
use App\Models\Gate;
use App\Models\SmsMessage;
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
     * Код ответа
     * 
     * @var null|int
     */
    public $response_code = null;

    /**
     * Создание экземпляра объекта
     * 
     * @param int $row
     * @return void
     */
    public function __construct($row)
    {
        $this->sms = $row;
    }

    /**
     * Обработка очреди
     * 
     * @return boolean
     */
    public function start()
    {
        if (!$this->sms or !$gate = $this->getGateData($this->sms->gate))
            return false;

        extract($gate);

        if (empty($address) or empty($login) or empty($password))
            return false;

        $phone = $this->decrypt($this->sms->phone);
        $phone = "%2B" . $this->checkPhone($this->sms->phone, false);

        $url = "http://{$address}/cgi/WebCGI?1500101=account={$login}&password={$password}&port={$this->sms->channel}&destination={$phone}&content=" . urlencode($this->sms->message);

        $response = $this->sendGateRequest($url);

        if (is_array($response)) {

            $sent = false;

            if (in_array($response['Response'] ?? null, ["Error", "Failed"]) or $this->response_code != 200) {
                $this->sms->failed_at = now();
            } else {
                $this->sms->sent_at = now();
                $sent = true;
            }

            $this->sms->response = json_encode($response, JSON_UNESCAPED_UNICODE);
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
