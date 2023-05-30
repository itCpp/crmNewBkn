<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Requests\AddRequest;
use App\Http\Controllers\Requests\AddRequestCounterTrait;
use App\Http\Controllers\Requests\RequestChange;
use App\Http\Controllers\Users\UsersMerge;
use App\Models\Base\CrmComing;
use App\Models\CrmMka\CrmNewIncomingQuery;
use App\Models\RequestsClient;
use App\Models\RequestsComment;
use App\Models\RequestsRow;
use App\Models\User;
use App\Models\CrmMka\CrmRequest;
use App\Models\CrmMka\CrmRequestsRemark;
use App\Models\CrmMka\CrmRequestsSbComment;
use App\Models\IncomingQuery;
use App\Models\RequestsRowsConfirmedComment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class RequestsMerge extends RequestsMergeData
{
    use AddRequestCounterTrait;

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
    public $sourceToSource = [];

    /**
     * Сопостовление ресурсов источника
     * 
     * @var array
     */
    public $sourceResourceToRecource = [];

    /**
     * Соотношение статуса старой заявки к идентификтору нового статуса
     * 
     * @var array
     */
    public $stateToStatusId = [];

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
        $this->sourceToSource = $this->sourceToSource();
        $this->sourceResourceToRecource = $this->sourceResourceToRecource();
        $this->stateToStatusId = $this->stateToStatusId();

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

        // Перенос истории обращений
        // $this->findAndRequestQueries($new);

        // Формирование галочки достоверности сути обращения
        if (in_array($row->verno, ["1", "2"]))
            $this->findAndWriteConfimedComment($row->id, (int) $row->verno);

        /** Счетчик обращений по источникам */
        $this->countQuerySourceResource($new->source_id, $new->sourse_resource);

        return $new;
    }

    /**
     * Запись информации о галочке
     * 
     * @param  int $id
     * @param  int $verno
     * @return null
     */
    public function findAndWriteConfimedComment($id, $verno)
    {
        try {

            $coming = CrmComing::where('unicIdClient', $id)->first();

            $pins = explode("/", ($coming->lawyerPin ?? ""));

            foreach ($pins as $pin) {
                RequestsRowsConfirmedComment::create([
                    'request_id' => $id,
                    'confirmed' => (int) $verno == 1 ? true : false,
                    'confirm_pin' => (int) $pin > 0 ? $pin : null,
                ]);
            }
        } catch (Exception $e) {
        }

        return null;
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
     * Поиск и перенос истории обращений
     * 
     * @param \App\Models\RequestsRow $row
     * @return null
     */
    public function findAndRequestQueries($row)
    {
        CrmNewIncomingQuery::where('id_request', $row->id)
            ->orderBy('created_at')
            ->get()
            ->each(function ($item) {

                $query_data = is_array($item->request) ? $item->request : [];

                if (isset($query_data['number']))
                    $query_data['phone'] = $query_data['number'];

                $hash_phone = isset($query_data['phone'])
                    ? $this->hashPhone($query_data['phone'])
                    : null;

                if (!$hash_phone and $item->phone) {
                    $query_data['phone'] = $item->phone;
                    $hash_phone = $this->hashPhone($item->phone);
                }

                $client_id = optional(RequestsClient::where('hash', $hash_phone)->first())->id;

                $hash_phone_resource = isset($query_data['myPhone'])
                    ? $this->hashPhone($query_data['myPhone'])
                    : null;

                if (!$hash_phone_resource and $item->myPhone) {
                    $query_data['myPhone'] = $item->myPhone;
                    $hash_phone_resource = $this->hashPhone($item->myPhone);
                }

                $ad_source = isset($query_data['utm_source'])
                    ? $query_data['utm_source'] : null;

                if (isset($query_data['phone']))
                    $query_data['phone'] = $this->encrypt($query_data['phone']);

                if (isset($query_data['number']))
                    $query_data['number'] = $this->encrypt($query_data['number']);

                if (isset($query_data['ip'])) {
                    $item->ip = $query_data['ip'];
                    $query_data['ip_from_item'] = $item->ip;
                }

                if ($item->typeReq == "Звонок")
                    $type = "call";
                else if ($item->typeReq == "Текст")
                    $type = "text";

                $query_data['hash_gate'] = $item->hash_gate;
                $query_data['xml_cdr_uuid'] = $item->xml_cdr_uuid;
                $query_data['cdr_date'] = $item->cdr_date;

                $request_data = [
                    'id' => $item->id_request,
                ];

                foreach ($this->sourceToSource as $source) {
                    if ($source[1] == $item->type) {
                        $request_data['source_id'] = $source[0];
                        break;
                    }
                }

                $phone = $this->checkPhone($item->myPhone, 3);
                $site = isset($query_data['site']) ? $query_data['site'] : null;

                foreach ($this->sourceResourceToRecource as $source) {
                    if ($source[1] == $phone or $source[1] == $site) {
                        $request_data['sourse_resource'] = $source[0];
                        break;
                    }
                }

                $create = [
                    'query_data' => $query_data,
                    'client_id' => $client_id,
                    'request_id' => $item->id_request,
                    'ad_source' => $ad_source,
                    'type' => $type ?? null,
                    'hash_phone' => $hash_phone,
                    'hash_phone_resource' => $hash_phone_resource,
                    'request_data' => $request_data,
                    'ip' => $item->ip,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];

                IncomingQuery::create($create);
            });

        return null;
    }
}
