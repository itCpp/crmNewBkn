<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Requests\AddRequest;
use App\Http\Controllers\Controller;
use App\Models\RequestsClient;
use App\Models\RequestsRow;
use App\Models\RequestsSource;
use App\Models\User;
use App\Models\CrmMka\CrmRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class RequestsMerge extends Controller
{
    /** Идентификатор последней првоерки @var int */
    protected $lastId;

    /** Количество заявок в старой базе @var int */
    public $count;

    /** Максимальный идентификатор заявки @var int */
    public $max;

    /** Экземпляр объекта создания заявки @var AddRequest */
    protected $add;

    /** Массив сопоставления идентификаторов источников @var array */
    public $sourceToSource = [
        [2, 'БАСМАНКА'],
        [14, 'БЗБ'],
        [6, 'БРАКИ'],
        [15, 'ГАЗЕТА'],
        [1, 'ГОСЮРИСТ'],
        [11, 'Департамент юридических услуг'],
        [4, 'ДОСТ'],
        [10, 'Коллегия адвокатов'],
        [16, 'КОНСАЛТИНГ'],
        [8, 'Московская коллегия адвокатов'],
        [17, 'Подарки от Худякова'],
        [9, 'правовые эксперты России'],
        [3, 'СПР'],
        [5, 'ХУД'],
        [12, 'ЦПП'],
        [7, 'Эксперты права'],
        [18, 'ЮК'],
        [19, 'Юридический центр'],
        [20, 'ЮРИСКОНСУЛЬТ'],
        [21, 'ЮРСЛУЖБА'],
    ];

    /** Сопоставления источников */

    /**
     * Объвление нового экзкмпляра
     * 
     * @return void
     */
    public function __construct()
    {
        $this->lastId = 0;
        $this->count = CrmRequest::count();
        $this->max = CrmRequest::max('id');

        $request = new Request;
        $this->add = new AddRequest($request);
    }

    /**
     * Метод поиска строки
     * 
     * @return false|CrmRequest
     */
    public function getRow()
    {
        if (!$row = CrmRequest::where('id', '>', $this->lastId)->first())
            return false;

        $this->lastId = $row->id;

        return $row;
    }

    /**
     * Обработка заявки
     * 
     * @return false|CrmRequest
     */
    public function step()
    {
        if (!$row = $this->getRow())
            return false;

        $data = (object) [
            'row' => $row->toArray(),
        ];

        $create = [];

        // Определение номеров клиента
        $data->phones = $this->getPhones($row);

        // Поиск и/или создание клиентов
        $data->clients = $this->chenckOrCreteClients($data->phones);

        $create['query_type'] = $this->getQueryType($row);
        $create['callcenter_sector'] = $this->getSectorId($row);
        $create['pin'] = $this->getNewPin($row);
        $create['source_id'] = $this->getSourceId($row);
        $create['client_name'] = $row->name != "" ? $row->name : null;
        $create['theme'] = null;
        $create['region'] = null;
        $create['check_moscow'] = null;
        $create['comment'] = null;
        $create['comment_urist'] = null;
        $create['comment_first'] = null;
        $create['status_id'] = null;
        $create['address'] = null;
        $create['event_at'] = null;
        $create['uplift'] = 0;
        $create['uplift_at'] = null;
        $create['created_at'] = null;
        $create['updated_at'] = null;
        $create['deleted_at'] = null;

        $data->new = new RequestsRow;

        dd($data, $create);

        return $row;
    }

    /**
     * Поиск номеров телефона в старой заявке
     * 
     * @param CrmRequest $row
     * @return array
     */
    public function getPhones($row)
    {
        if ($phone = $this->checkPhone($row->phone, 1))
            $phones[] = $phone;

        $seconds = explode("|", $row->secondPhone);

        if (is_array($seconds)) {
            foreach ($seconds as $second) {
                if ($phone = $this->checkPhone($second, 1))
                    $phones[] = $phone;
            }
        }

        return array_unique($phones ?? []);
    }

    /**
     * Проверка и создание клиентов
     * 
     * @param array $phones
     * @return array
     */
    public function chenckOrCreteClients($phones)
    {
        foreach ($phones as $phone) {

            $hash = $this->add->getHashPhone($phone);

            $clients[] = RequestsClient::firstOrCreate(
                ['hash' => $hash],
                ['phone' => Crypt::encryptString($phone)]
            );
        }

        return $clients ?? [];
    }

    /**
     * Определение типа заявки
     * 
     * @param CrmRequest
     * @return string
     */
    public function getQueryType($row)
    {
        return $row->typeReq == "Звонок" ? "call" : "text";
    }

    /**
     * Определение сектора
     * 
     * @param CrmRequest
     * @return null|int
     */
    public function getSectorId($row)
    {
        $sector = $row->{'call-center'};

        if ($sector === "" or $sector === null)
            return null;

        $sectors = [
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1,
            5 => 1,
            6 => 1,
            7 => 2,
        ];

        return $sectors[(int) $sector] ?? null;
    }

    /**
     * Определение источника старой заявки
     * 
     * @param CrmRequest
     * @return null|int
     */
    public function getSourceId($row)
    {
        foreach ($this->sourceToSource as $source) {
            if ($row->type == $source[1])
                return $source[0];
        }

        return null;
    }

    /**
     * Поиск нового пина сотрудника
     * 
     * @param CrmRequest
     * @return int|string
     */
    public function getNewPin($row)
    {
        if (!$user = User::where('old_pin', $row->pin)->first())
            return $row->pin;

        return $user->pin;
    }
}
