<?php

namespace App\Console\Commands;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings;
use App\Http\Controllers\Gates\GateBase64;
use App\Http\Controllers\Gates\Gates;
use App\Http\Controllers\Requests\AddRequest;
use App\Models\Gate;
use App\Models\SmsMessage;
use App\Models\RequestsClient;
use Illuminate\Console\Command;

class SmsIncomingsCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:incomings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check incoming sms from gsm-gates';

    /**
     * Список шлюзов для проверки
     * 
     * @var \App\Models\Gate[]
     */
    protected $gates;

    /**
     * Экземпляр объекта еодировани/декодирования сообщения
     * 
     * @var \App\Http\Controllers\Gates\GateBase64
     */
    protected $base64;

    /**
     * Полученные сообщения
     * 
     * @var array
     */
    protected $messages = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->settings = new Settings('CRONTAB_SMS_INCOMINGS_CHECK');

        $this->gates = (new Gates('sms_incomings'))->get();
        $this->base64 = new GateBase64;

        if (!$this->settings->CRONTAB_SMS_INCOMINGS_CHECK) {
            $this->line(date("[Y-m-d H:i:s]") . " Проверка СМС ОТКЛЮЧЕНА в настройках ЦРМ");
            return 0;
        }

        if (count($this->gates) == 0) {
            $this->line(date("[Y-m-d H:i:s]") . " Шлюзы для проверки СМС не настроены");
            return 0;
        }

        foreach ($this->gates as $gate) {
            $this->parseData($gate);
        }

        return 0;
    }

    /**
     * Подключение к шлюзу и обработка данных
     * 
     * @param \App\Models\Gate $gate
     * @return mixed $this
     */
    public function parseData(Gate $gate)
    {
        $cookie = "Cookie: " . Gates::getHeaderString($gate->headers->Cookie ?? null, [
            'current' => "sms",
            'curUrl' => "15100",
        ]);

        $cmd = "wget http://{$gate->addr}/cgi/WebCGI?15200 -qO- --no-cookies --timeout=8 --tries=1 --header \"$cookie\"";

        $html = shell_exec($cmd);
        $messages = [];

        preg_match_all('~(<input type="hidden" id="MyPBX_COMM" value="(.*?)">)~', $html, $matches);

        $value = end($matches);
        $value = end($value);

        $array = [];

        $parts = explode("&", $value);

        foreach ($parts as $part) {

            $data = explode(";", $part);

            foreach ($data as $row) {
                if (
                    $row != ""
                    and strpos($row, "Filter:") === false
                    and strpos($row, "DisplayInfo:") === false
                    and strpos($row, "Trunks:") === false
                    and strpos($row, "FilterPlus:") === false
                    and strpos($row, "Gsmport:") === false
                    and strpos($row, "SearchInfos:") === false
                    and strpos($row, "Display:") === false
                ) {
                    $row = str_replace("SMSRecvList:", "", $row);
                    $array[] = explode("^^", $row);
                }
            }
        }

        foreach ($array as $row) {
            if (isset($row[0]) and isset($row[1]) and isset($row[2]) and isset($row[3]) and isset($row[4])) {

                $message = [
                    'message_id' => $row[0],
                    'gate' => $gate->id,
                    'channel' => (int) $row[1],
                    'phone' => Controller::encrypt($row[2]),
                    'message' => $row[4],
                    'direction' => "in",
                    'sent_at' => $row[3],
                ];

                $this->messages[] = $message;
                $messages[] = $message;
            }
        }

        $this->line(date("[Y-m-d H:i:s]") . "[{$gate->addr}] Найдено сообщений: " . count($messages));
        $this->checkAndCreateMessages($messages);

        return $this;
    }

    /**
     * Проверка и создания сообщения в БД
     * 
     * @param array $messages
     * @return null
     */
    public function checkAndCreateMessages($messages = [])
    {
        $checks = SmsMessage::where(function ($query) use ($messages) {
            foreach ($messages as $message) {
                $query->orWhere([
                    ['message_id', $message['message_id']],
                    ['gate', $message['gate']],
                    ['channel', $message['channel']],
                ]);
            }
        })
            ->get()
            ->map(function ($row) {
                return md5($row->message_id . $row->gate . $row->channel);
            })
            ->toArray();

        $created = [];

        foreach ($messages as $message) {

            $hash = md5($message['message_id'] . $message['gate'] . $message['channel']);

            if (in_array($hash, $checks))
                continue;

            $sms = SmsMessage::create($message);
            $sms = $this->findRequests($sms);

            $requests = count($sms->requests);

            $this->line(date("[Y-m-d H:i:s]") . "[NEW {$sms->message_id}][{$requests}]");

            $created[] = $sms;
        }

        if (count($created) == 0)
            $this->line(date("[Y-m-d H:i:s]") . " Новых сообщений не поступало");

        return null;
    }

    /**
     * Поиск заявок по номеру телефона
     * 
     * @param SmsMessage $row
     * @return SmsMessage
     */
    public function findRequests($row)
    {
        $phone = Controller::checkPhone(Controller::decrypt($row->phone));
        $hash = AddRequest::getHashPhone($phone);

        if (!$client = RequestsClient::where('hash', $hash)->first())
            return $row;

        foreach ($client->requests as $request) {
            $row->requests()->attach($request->id);
        }

        return $row;
    }
}
