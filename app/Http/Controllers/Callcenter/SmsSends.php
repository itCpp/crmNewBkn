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
     * Создание экземпляра объекта
     * 
     * @param int $row
     * @return void
     */
    public function __construct($row)
    {
        $this->sms = SmsMessage::find($row);
    }

    /**
     * Обработка очреди
     * 
     * @return false|array
     */
    public function start()
    {
        if (!$gate = $this->getGateData($this->sms->gate))
            return false;

        extract($gate);

        if (empty($address) or empty($login) or empty($password))
            return false;

        $phone = $this->decrypt($this->sms->phone);
        $phone = "%2B" . $this->checkPhone($this->sms->phone, false);

        $url = "http://{$address}/cgi/WebCGI?1500101=account={$login}&password={$password}&port={$this->sms->channel}&destination={$phone}&content=" . urlencode($this->sms->message);

        $response = $this->sendGateRequest($url);

        if (is_array($response)) {
            $this->sms->response = json_encode($response, JSON_UNESCAPED_UNICODE);
            $this->send_at = now();
            $this->sms->save();

            return $response;
        }

        return true;
    }

    /**
     * Поиск данных шлюза
     * 
     * @param int|null $id
     * @return array|null
     */
    public function getGateData($id = null)
    {
        if (!$gate = Gate::where('id', $id)->where('for_sms')->first())
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
            CURLOPT_CONNECTTIMEOUT  => 5,           // timeout on connect (in seconds)
            CURLOPT_TIMEOUT         => 5,           // timeout on response (in seconds)
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode != 200)
            return false;

        $data = [];
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
