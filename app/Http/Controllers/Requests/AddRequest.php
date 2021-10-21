<?php

namespace App\Http\Controllers\Requests;

use App\Events\CreatedNewRequest;
use App\Events\UpdateRequestRow;
use App\Events\Requests\AddRequestEvent;
use App\Events\Requests\UpdateRequestEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Dev\Statuses;
use App\Models\IncomingQuery;
use App\Models\RequestsClient;
use App\Models\RequestsComment;
use App\Models\RequestsRow;
use App\Models\RequestsSource;
use App\Models\RequestsSourcesResource;
use App\Models\RequestsStory;
use App\Models\Status;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Оаработка входящих запросов для создания новой, обнуления и обновления заявки
 * 
 * Основной метод обработки
 * @method add()
 * 
 * @method findClient()
 * @method findSource()
 * @method findRequest()
 * @method requestAnalise()
 * @method checkZeroing()
 * @method requestZeroing()
 * @method requestSave()
 * @method checkPin()
 * @method createNewRequest()
 * 
 * @method writeQuery()
 */
class AddRequest extends Controller
{
    /**
     * Данные запроса
     * 
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Номер телефона входящего запроса
     * 
     * @var string|bool
     */
    protected $phone;

    /**
     * Данные клиента
     * 
     * @var null|\App\Models\RequestsClient
     */
    protected $client = null;

    /**
     * Данные ресурса
     * 
     * @var null|\App\Models\RequestsSourcesResource
     */
    protected $resource = null;

    /**
     * Данные источника
     * 
     * @var null|\App\Models\RequestsSource
     */
    protected $source = null;

    /**
     * Данные обработанной заявки
     * 
     * @var null|false|\App\Models\RequestsRow
     */
    protected $data = null;

    /**
     * Массив сообщений об ошибке
     * 
     * @var array
     */
    protected $errors = [];

    /**
     * Созданные комментарии
     * 
     * @var array
     */
    protected $comments = [];

    /**
     * Флаг создания новой заявки
     * 
     * @var bool
     */
    protected $created = false;

    /**
     * Массив данных о результате обработки заявки
     * 
     * @var array
     */
    public $response = [];

    /**
     * Инициализация объекта
     * 
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Controllers\Requests\AddRequest
     */
    public function __construct(Request $request)
    {
        $this->request = $request;

        // Номер телефона клиента
        $this->phone = $this->checkPhone($this->request->phone);

        // Номер телефона источника
        $this->myPhone = $this->checkPhone($this->request->myPhone);
        // Адрес сайта источника
        $this->site = $this->request->site;

        // Тип обращения по входящим данным
        if ($request->query_type)
            $this->query_type = $request->query_type;
        elseif ($this->myPhone)
            $this->query_type = "call";
        elseif ($this->site)
            $this->query_type = "text";

        // Логирование всех запросов
        $this->queryLog = IncomingQuery::create([
            'ip' => $this->request->ip(),
            'user_agent' => $this->request->header('User-Agent'),
        ]);

        $this->response = [];

        $this->zeroing = false; // Идентфиикатор удаленной заявки
    }

    /**
     * Магический метод для вывода несуществующего значения
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->$name) === true)
            return $this->$name;

        return null;
    }

    /**
     * Вывод результата
     * 
     * @return array|response
     */
    public function response()
    {
        $this->writeQuery();

        // Отправка события о завершении обработки
        broadcast(new AddRequestEvent($this->data, $this->response));

        // Вывод массива данных
        if ($this->request->responseData || $this->request->manual)
            return $this->response;

        return response()->json($this->response);
    }

    /**
     * Вывод плохого запроса
     * 
     * @return array|response
     */
    public function badRequest()
    {
        $this->response['done'] = "fail";
        $this->response['message'] = "Запрос не обработан";

        if ($this->errors) {
            $this->response['errors'] = $this->errors;
        }

        return $this->response();
    }

    /**
     * Добавление заявки
     * 
     * @return response
     */
    public function add()
    {
        $this->findClient(); // Поиск клиента

        if (!$this->phone)
            return $this->badRequest();

        $this->findSource() // Поиск источника
            ->findRequest() // Поиск заявки клиента по источнику
            ->requestAnalise() // Анализ существующей заявки
            ->requestSave(); // Сохранение заявки

        $this->response = [
            'done' => "success",
            'message' => "Запрос обработан",
            // 'request' => $this->data,
            'requestId' => $this->data->id ?? null, // Идентификатор заявки
            'zeroing' => $this->zeroing, // Информация об обнулении
            // 'client' => $this->client,
            'clientId' => $this->client->id ?? null, // Идентификатор клиента
            // 'source' => $this->source,
            // 'resource' => $this->resource,
            'status' => $this->status,
            // 'query' => $this->query,
            'comments' => count($this->comments),
            'created' => $this->created, // Флаг новой заявки
        ];

        if ($this->errors) {
            $this->response['done'] = "fail";
            $this->response['errors'] = $this->errors;
        }

        return $this->response();
    }

    /**
     * Формирование хэша номера телефона
     * 
     * @param string $phone
     * @return string
     */
    public static function getHashPhone($phone)
    {
        return md5($phone . env('APP_KEY'));
    }

    /**
     * Поиск клиента по номеру телефона
     * 
     * @return $this
     */
    public function findClient()
    {
        // Отмена запроса
        if (!$this->phone) {
            $this->errors['phone'][] = "Номер телефона клиента не определен";
            return $this;
        }

        $hash = $this->getHashPhone($this->phone);

        // Поиск или создание нового клиента
        if (!$this->client = RequestsClient::where('hash', $hash)->first()) {
            $this->client = RequestsClient::create([
                'phone' => Crypt::encryptString($this->phone),
                'hash' => $hash,
            ]);
        }

        return $this;
    }

    /**
     * Поиск источника по ресурсу
     * 
     * @return $this
     */
    public function findSource()
    {
        // Вывод источника при ручном создании заявки
        if ($this->request->manual and $this->request->source) {
            $this->source = (object) [
                'id' => $this->request->source
            ];
            return $this;
        }

        if (!$this->client)
            return $this;

        if (!$this->myPhone and !$this->site) {
            $this->errors['source'][] = "Источник не определен";
            return $this;
        }

        $query = RequestsSourcesResource::query();

        $query->when($this->myPhone, function ($query) {
            return $query->where('val', $this->myPhone);
        });

        $query->when($this->site, function ($query) {
            return $query->where('val', $this->site);
        });

        $this->resource = $query->first();
        $this->source = $this->resource->source ?? null;

        if ($this->resource and !$this->source) {
            $this->errors['source'][] = "Источник по ресурсу не определен";
        }

        // Обновлние типа обращения по ресурсу источника
        if ($this->resource) {

            if ($this->resource->type == "phone")
                $this->query_type = "call";
            elseif ($this->resource->type == "site")
                $this->query_type = "text";
        }

        return $this;
    }

    /**
     * Поиск заявки у клиента по источнику
     * 
     * @return $this
     */
    public function findRequest()
    {
        if (!$this->client)
            return $this;

        $this->data = $this->client->requests()->where('source_id', $this->source->id ?? null)->first();

        return $this;
    }

    /**
     * Анализ существующей заявки
     * 
     * @return $this
     */
    public function requestAnalise()
    {
        if (!$this->data)
            return $this;

        $this->status = Status::find($this->data->status_id);

        // Проверка для обнуления заявки
        if ($this->checkZeroing())
            return $this->requestZeroing();

        return $this;
    }

    /**
     * Проверка необходимости обнуления
     * 
     * @return bool
     */
    public function checkZeroing()
    {
        if (!$this->status)
            return false;

        if (!$this->status->zeroing)
            return false;

        if (!$data = json_decode($this->status->zeroing_data))
            return false;

        // Информация об алгоритме
        if (!$algorithm = Statuses::findAlgorithm($data->algorithm ?? null))
            return false;

        // Проверка дополнительного параметра
        if ($algorithm['option'] and ($data->algorithm_option ?? null) === null)
            return false;

        $time_created = $data->time_created ?? false; // Время создания
        $time_event = $data->time_event ?? false; // Время события
        $time_updated = $data->time_updated ?? false; // Время обновления

        $time = time();
        $last = null;

        $created = $time_created ? date("Y-m-d H:i:s", strtotime($this->data->created_at)) : null;
        $event = $time_event ? date("Y-m-d H:i:s", strtotime($this->data->event_at)) : null;
        $updated = $time_updated ? date("Y-m-d H:i:s", strtotime($this->data->updated_at)) : null;

        if ($time_created and $created and $last < $created)
            $last = $created;

        if ($time_event and $event and $last < $event)
            $last = $event;

        if ($time_updated and $updated and $last < $updated)
            $last = $updated;

        if ($algorithm['name'] == "xHour")
            $date = date("Y-m-d H:i:s", $time - ($algorithm['option'] * 60 * 60));
        elseif ($algorithm['name'] == "xDays")
            $date = date("Y-m-d H:i:s", $time - ($algorithm['option'] * 24 * 60 * 60));
        elseif ($algorithm['name'] == "nextDay")
            $date = date("Y-m-d 00:00:00", $time - (24 * 60 * 60));

        if (!isset($date))
            return false;

        if ($last >= $date)
            return false;

        return true;
    }

    /**
     * Обнуление заявки по условиям статуса
     * 
     * @return $this
     */
    public function requestZeroing()
    {
        $this->zeroing = $this->data->id;

        $this->data->delete();
        $this->createNewRequest();

        return $this;
    }

    /**
     * Заполнение поступивших данных и сохранение заявки
     * Добавление комментариев и прочего
     * 
     * @return $this
     */
    public function requestSave()
    {
        if (!$this->data)
            $this->createNewRequest();

        $this->data->query_type = $this->query_type; # Тип обращения

        // Время подъема
        $this->data->uplift = 1;
        $this->data->uplift_at = date("Y-m-d H:i:s");

        // Проверка и/или обновлние оператора
        $this->data->pin = $this->checkPin();

        // Обновление и дополнение данными
        $this->addData();

        $this->data->save();

        // Логирование изменений заявки
        RequestsStory::write($this->request, $this->data);

        // $row = Requests::getRequestRow($this->data); // Полные данные по заявке

        // // Отправка события о новой заявке
        // if ($this->created) {
        //     // broadcast(new CreatedNewRequest($row, $this->zeroing));
        // }
        // // Отправка события об изменении заявки
        // else {
        //     broadcast(new UpdateRequestEvent($row, false));
        // }

        return $this;
    }

    /**
     * Проверка сотрудника на момент подъема заявки
     * 
     * @return null|string
     */
    public function checkPin()
    {
        // Отмена проверки
        if (!$this->data->pin)
            return null;

        // Поиск сотрудника по пину
        if (!$user = User::where('pin', $this->data->pin)->first())
            return null;

        // Сотрудник заблокирован или уволен
        if ($user->deleted_at)
            return null;

        return $user->pin;
    }

    /**
     * Создание новой заявки
     * 
     * @return $this
     */
    public function createNewRequest()
    {
        $this->data = RequestsRow::create([
            'source_id' => $this->source->id ?? null,
            'sourse_resource' => $this->resource->id ?? null,
        ]);

        $this->created = true;

        // Добавление отношения клиента к заявки
        $this->client->requests()->attach($this->data->id);

        return $this;
    }

    /**
     * Запись входящего запроса
     * 
     * @return \App\Models\IncomingQuery
     */
    public function writeQuery()
    {
        $data = $this->request->all();

        if (isset($data['phone']))
            $data['phone'] = $this->encrypt($data['phone']);

        $this->queryLog->query_data = $data; # Поступившие данные
        $this->queryLog->client_id = $this->client->id ?? null; # Идентификатор клиента
        $this->queryLog->request_id = $this->data->id ?? null; # Идентификатор заявки
        $this->queryLog->request_data = $this->data ?? []; # Данные в заявке
        $this->queryLog->response_data = $this->response; # Массив ответа

        $this->queryLog->save();

        return $this->queryLog;
    }

    /**
     * Проверка и добавление данных в заявку
     * 
     * @return $this
     */
    public function addData()
    {
        // Проверка имени клиента
        if ($this->request->client_name) {

            if (!$this->data->client_name)
                $this->data->client_name = $this->request->client_name;
            else
                $this->addComment("Клиент представился: {$this->request->client_name}", "client");
        }

        // Комментарий клиента
        if ($this->request->comment) {

            if (!$this->data->comment)
                $this->data->comment = $this->request->comment;
            else
                $this->addComment("Клиент написал: {$this->request->comment}", "client");
        }

        // Главный комментарий
        if ($this->request->comment_main) {

            if (!$this->data->comment)
                $this->data->comment = $this->request->comment_main;
            else
                $this->addComment("Суть обращения: {$this->request->comment_main}", "client");
        }

        // Первичный комментарий
        if ($this->request->comment_first) {

            if (!$this->data->comment_first)
                $this->data->comment_first = $this->request->comment_first;
            else
                $this->data->comment_first .= date(" d.m.Y H:i ") . $this->request->comment_first;
        }

        // Смена города
        if ($this->request->city) {

            if ($this->data->region and $this->request->city != $this->data->region)
                $this->addComment("Смена города с \"{$this->data->region}\" на \"{$this->request->city}\"");

            $this->data->region = $this->request->city;
            $this->data->check_moscow = RequestChange::checkRegion($this->data->region);
        }

        // Смена тематики обращения
        if ($this->request->theme) {

            if ($this->data->theme and $this->request->theme != $this->data->theme)
                $this->addComment("Смена тематики с \"{$this->data->theme}\" на \"{$this->request->theme}\"");

            $this->data->theme = $this->request->theme;
        }

        return $this;
    }

    /**
     * Добавление комментария по заявке
     * 
     * @param string|null $comment Текст комментария
     * @param string $type Тип комментария
     * @return $this
     */
    public function addComment($comment = null, $type = "comment")
    {
        if (!$comment)
            return $this;

        $this->comments[] = RequestsComment::create([
            'request_id' => $this->data->id,
            'type_comment' => $type,
            'comment' => $comment,
        ]);

        return $this;
    }
}
