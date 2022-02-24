<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Requests\AddRequest;
use App\Http\Controllers\Requests\RequestChange;
use App\Http\Controllers\Users\UsersMerge;
use App\Models\CrmMka\CrmNewRequestsState;
use App\Models\CrmMka\CrmNewRequestsStory;
use App\Models\RequestsClient;
use App\Models\RequestsComment;
use App\Models\RequestsRow;
use App\Models\User;
use App\Models\CrmMka\CrmRequest;
use App\Models\CrmMka\CrmRequestsRemark;
use App\Models\CrmMka\CrmRequestsSbComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class RequestsMerge extends Controller
{
    /**
     * Идентификатор последней проверки
     * 
     * @var int
     */
    protected $lastId;

    /**
     * Количество заявок в старой базе
     * 
     * @var int
     */
    public $count;

    /**
     * Максимальный идентификатор заявки
     * 
     * @var int
     */
    public $max;

    /**
     * Экземпляр объекта создания заявки
     * 
     * @var \App\Http\Controllers\Requests\AddRequest
     */
    protected $add;

    /**
     * Массив сопоставления идентификаторов источников
     * 
     * @var array
     */
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
        [12, null],
        [7, 'Эксперты права'],
        [18, 'ЮК'],
        [19, 'Юридический центр'],
        [20, 'ЮРИСКОНСУЛЬТ'],
        [21, 'ЮРСЛУЖБА'],
    ];

    /**
     * Сопостовление ресурсов источника
     * 
     * @var array
     */
    public $sourceResourceToRecource = [
        [3, '84951978661'],
        [4, '88005002096'],
        [5, '88005002489'],
        [1, 'gosyurist.ru'],
        [2, 'ros-yuristy.ru'],
        [7, '84951978120'],
        [8, '84952331264'],
        [9, '88002503210'],
        [10, '88003333404'],
        [6, 'yuris-konsult.ru'],
        [12, '88005000380'],
        [11, 'xn--g1acavabdidhea6a9gxb.xn--p1ai'],
        [13, 'профсоюзыроссии.рф'],
        [14, 'dostoinaya-zhizn.ru'],
        [16, '89167324970'],
        [15, 'hudyakovroman.ru'],
        [18, '84995509611'],
        [19, 'm.эксперты-права.рф'],
        [17, 'xn----8sbahm3a9achcfp1jva.xn--p1ai'],
        [20, 'эксперты-права.рф'],
        [23, '84951233027'],
        [24, '84952270665'],
        [25, '88005001573'],
        [21, 'xn----8sbf5ajmeav8b.xn--p1ai'],
        [22, 'xn--o1aat.xn--p1ai'],
        [26, 'цпп-москва.рф'],
        [27, 'цпп.рф'],
        [29, '84951980812'],
        [28, 'yur-experts.ru'],
        [31, '84952212706'],
        [32, '84996732340'],
        [30, 'civil-right.ru'],
        [34, '84996733020'],
        [33, 'yurcentre.ru'],
    ];

    /**
     * Соотношение статуса старой заявки к идентификтору нового статуса
     * 
     * @var array
     */
    public $stateToStatusId = [
        'bk' => 5,
        'brak' => 6,
        'color-promo' => 12,
        'nedozvon' => 1,
        'neobr' => null,
        'online' => 10,
        'online-doc' => 11,
        'podtverjden' => 4,
        'prihod' => 7,
        'promo' => 9,
        'shipping' => 14,
        'sliv' => 8,
        'sozvon' => 2,
        'vtorich' => 13,
        'zapis' => 3,
    ];

    /**
     * Проверяемые сотрудники
     * 
     * @var array
     */
    protected $users = [];

    /**
     * Проверенные персональне номера сотрудников
     * 
     * @var array
     */
    protected $new_pins = [];

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

        $this->usersMerge = new UsersMerge;
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

        // Проверка наличия заявки в новой БД для пропуска
        if ($check = RequestsRow::find($row->id))
            return $check;

        $data = (object) [];

        $data->row = $row->toArray(); # Данные старой заявки
        $data->phones = $this->getPhones($row); # Номера телефонов клиента

        // Поиск и/или создание клиентов
        $data->clients = $this->chenckOrCreteClients($data->phones);

        $new = new RequestsRow;

        $new->id = $row->id;
        $new->query_type = $this->getQueryType($row);
        $new->callcenter_sector = $this->getSectorId($row);
        $new->pin = $this->getNewPin($row->pin);
        $new->source_id = $this->getSourceId($row);
        $new->sourse_resource = $this->getResourceId($row);
        $new->client_name = $row->name != "" ? $row->name : null;
        $new->theme = $row->theme != "" ? $row->theme : null;
        $new->region = ($row->region != "" and $row->region != "Неизвестно") ? $row->region : null;
        $new->check_moscow = $new->region ? RequestChange::checkRegion($new->region) : null;
        $new->comment = $row->comment != "" ? $row->comment : null;
        $new->comment_urist = $row->uristComment != "" ? $row->uristComment : null;
        $new->comment_first = $row->first_comment != "" ? $row->first_comment : null;
        $new->status_id = $this->getStatusId($row);
        $new->address = $row->address ?: null;
        $new->uplift = $row->vtorCall == "vtorCall" ? 1 : 0;

        $new->event_at = $this->getEventAt($row);
        $new->created_at = $this->getCreatedAt($row);
        $new->uplift_at = $this->getUpliftAt($row, $new);
        $new->deleted_at = $this->getDeletedAt($row, $new);
        $new->updated_at = $this->getUpdatedAt($new);

        if ($new->uplift == 1) {

            /** Обнуление подъема со статусом */
            if ($new->status_id)
                $new->uplift = 0;
            /** Обнуление заявок с необарботанным статусом для определенных источников */
            else if (!$new->status_id and in_array($new->source_id, [3, 4, 5, 6, 17]))
                $new->uplift = 0;
        }

        $new->save();

        // Привязка клиента к заявке
        foreach ($data->clients as $client) {
            $client->requests()->attach($new->id);
        }

        // Поиск и добавление комментариев по заявке
        $data->comments = $this->getAndCreateAllComments($new->id);

        // Поиск истории изменений заявки
        // $this->findAndWriteRequestsStory($new->id);

        return $new;
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
            3 => 2,
            4 => 2,
            5 => 2,
            6 => 2,
            7 => 3,
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
     * Определение ресурса источника заявки
     * 
     * @param CrmRequest
     * @return null|int
     */
    public function getResourceId($row)
    {
        foreach ($this->sourceResourceToRecource as $resource) {
            if ($row->typeSiteLink === $resource[1] or $this->checkPhone($row->myPhone, 3) === $resource[1])
                return $resource[0];
        }

        return null;
    }

    /**
     * Поиск нового пина сотрудника
     * 
     * @param string $pin
     * @return int|string
     */
    public function getNewPin($pin)
    {
        $key = md5($pin);

        if (!empty($this->new_pins[$key]))
            return $this->new_pins[$key];

        if (!$pin)
            return $this->new_pins[$key] = null;

        if (!$user = User::where('old_pin', $pin)->first())
            return $this->getFiredAndCreateNewUser($pin, $key);

        return $this->new_pins[$key] = $user->pin;
    }

    /**
     * Создание уволенного сотрудника
     * 
     * @param string $pin
     * @param null|string $key
     * @return int
     */
    public function getFiredAndCreateNewUser($pin, $key = null)
    {
        $key = $key ?: md5($pin);

        if (!$user = $this->usersMerge->createFiredUser($pin))
            return $this->new_pins[$key] = $pin;

        return $this->new_pins[$key] = $user->pin;
    }

    /**
     * Определение идентификатора статуса заявки
     * По умолчнию будет бракованная заявка, чтобы обнулить её при следующем поступлении
     * 
     * @param CrmRequest $row
     * @return null|int
     */
    public function getStatusId($row)
    {
        return $this->stateToStatusId[$row->state] ?? 6;
    }

    /**
     * Определение времени события
     * 
     * @param CrmRequest $row
     * @return null|string
     */
    public function getEventAt($row)
    {
        if (!$row->rdate or !$row->time)
            return null;

        $date = trim($row->rdate . " " . $row->time);
        $time = strtotime($date);

        if (!$time or $time > 1640995200 or $time < 1420070400)
            return null;

        return $date;
    }

    /**
     * Дата создания заявки
     * 
     * @param CrmRequest $row
     * @return null|string
     */
    public function getCreatedAt($row)
    {
        $date = $row->staticDate;

        if (!$date or $date == "")
            $date = $row->date;

        if (!$date or $date == "")
            return null;

        return trim($date . " " . $row->staticTime);
    }

    /**
     * Определение времени подъема заявки
     * 
     * @param CrmRequest $row
     * @param RequestsRow $new
     * @return null|string
     */
    public function getUpliftAt($row, $new)
    {
        $time = null;

        if ($row->timeSort and $row->timeSort != "")
            $time = $row->timeSort;

        if (!$time and $new->created_at)
            $time = strtotime($new->created_at);

        return $time ? date("Y-m-d H:i:s", $time) : null;
    }

    /**
     * Дата и время удаления заявки
     * 
     * @param CrmRequest $row
     * @param RequestsRow $new
     * @return null|string
     */
    public function getDeletedAt($row, $new)
    {
        if ($row->del != "hide" and $row->noView != 1)
            return null;

        if ($date = $this->getUpdatedAt($new))
            return $date;

        return now();
    }

    /**
     * Дата и время обновления
     * 
     * @param RequestsRow $new
     * @return null|string
     */
    public function getUpdatedAt($new)
    {
        $date = null;

        if (!$date or $new->created_at > $date)
            $date = $new->created_at;

        if (!$date or $new->event_at > $date)
            $date = $new->event_at;

        if (!$date or $new->uplift_at > $date)
            $date = $new->uplift_at;

        if (!$date or $new->uplift_at > $date)
            $date = $new->uplift_at;

        return $date;
    }

    /**
     * Поиск и добавление комментариев по заявке
     * 
     * @param int $id Идентификтаор заявки
     * @return int Количество комментариев
     */
    public function getAndCreateAllComments($id)
    {
        $count = 0;

        // Комментарии СБ
        foreach (CrmRequestsSbComment::where('request_id', $id)->get() as $row) {
            RequestsComment::create([
                'request_id' => $id,
                'type_comment' => "sb",
                'created_pin' => $this->getCommentAuthor($row->pin),
                'comment' => $row->comment,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);

            $count++;
        }

        // Заметки
        foreach (CrmRequestsRemark::where('idReq', $id)->get() as $row) {
            RequestsComment::create([
                'request_id' => $id,
                'type_comment' => "comment",
                'created_pin' => $this->getCommentAuthor($row->pin),
                'comment' => $row->remark,
                'created_at' => $row->create_at,
                'updated_at' => $row->create_at,
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Поиск сотрудника по старому пину
     * 
     * @param int|string $pin
     * @return int
     */
    public function getCommentAuthor($pin)
    {
        if (!empty($this->users[$pin]))
            return $this->users[$pin]->pin ?? $pin;

        if (!User::where('old_pin', $pin)->first())
            $this->users[$pin] = $this->getFiredAndCreateNewUser($pin);

        return $this->users[$pin]->pin ?? $pin;
    }

    /**
     * Поиск и запись истории изменения заявки
     * 
     * @param int $id
     * @return array
     */
    public function findAndWriteRequestsStory($id)
    {
        $state = null; // Отслеживание смены статуса
        $change_pin = null; // Отслеживание смены оператора

        return CrmNewRequestsStory::where('id_request', $id)
            ->get()
            ->map(function ($row) use (&$state, &$change_pin) {

                $new = new RequestsRow;

                $new->id = $row->id_request;
                $new->pin = $this->getNewPin($row->pin);

                if ($row->state = CrmNewRequestsState::where('idStory', $row->id)->first()) {
                    $new->status_id = $this->getStatusId($row->state);
                    $new->event_at = $row->state->date;
                }

                $new->client_name = $row->name != "" ? $row->name : null;
                $new->theme = $row->theme != "" ? $row->theme : null;
                $new->region = ($row->region != "" and $row->region != "Неизвестно") ? $row->region : null;
                $new->check_moscow = $new->region ? RequestChange::checkRegion($new->region) : null;
                $new->comment = $row->comment != "" ? $row->comment : null;
                $new->comment_urist = $row->uristComment != "" ? $row->uristComment : null;
                $new->address = $row->address ?: null;

                if ($row->newRequest) {
                    $new->uplift = 1;
                    $new->uplift_at = $row->create_at;
                }

                $new->updated_at = $row->create_at;

                $new->old_story = $row->id;

                $created_pin = $row->pinEdited ? $this->getNewPin($row->pinEdited) : null;

                $create['requests_stories'] = [
                    'request_id' => $row->id_request,
                    'request_data' => $new->toArray(),
                    'created_pin' => $created_pin,
                    'created_at' => $row->create_at,
                    // '__' => $row
                ];

                if ($row->state) {
                    $create['requests_story_statuses'] = [
                        'story_id' => $story->id ?? null,
                        'request_id' => $row->id_request,
                        'status_old' => $state,
                        'status_new' => $new->status_id,
                        'created_pin' => $row->id_request,
                        'created_at' => $row->create_at,
                    ];

                    $state = $new->status_id;
                }

                if ($new->pin != $change_pin) {
                    $create['requests_story_pins'] = [
                        'story_id' => $story->id ?? null,
                        'request_id' => $row->id_request,
                        'old_pin' => $change_pin,
                        'new_pin' => $new->pin,
                        'created_at' => $row->create_at,
                    ];
                }

                if ((!$change_pin and $new->pin) or ($new->pin and $change_pin))
                    $change_pin = $new->pin;

                return $create;
            })
            ->toArray();
    }
}
