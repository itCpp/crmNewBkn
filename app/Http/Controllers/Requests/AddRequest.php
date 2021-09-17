<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use App\Models\RequestsRow;
use App\Models\RequestsClient;
use App\Models\IncomingQuery;
use App\Models\RequestsSource;
use App\Models\RequestsSourcesResource;
use App\Models\Status;

/**
 * Оаработка входящих запросов для создания новой заявки
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

        // Тип входящего обращения
        if ($this->myPhone)
            $this->query_type = "call";
        elseif ($this->site)
            $this->query_type = "text";

    }

    /**
     * Добавление заявки
     * 
     * @return response
     */
    public function add()
    {

        $this->findClient()
            ->findSource()
            ->findRequest()
            ->requestAnalise()
            ->requestSave();

        $this->query = $this->writeQuery();

        $response = [
            'done' => "success",
            'message' => "Запрос обработан",
            'id' => $this->data->id ?? null, // Идентификатор заявки
            'zeroing' => $this->zeroing, // Информация об обнулении
            // 'client' => $this->client,
            // 'resource' => $this->resource,
            // 'source' => $this->source,
            'request' => $this->data,
            'status' => $this->status,
            // 'query' => $this->query,
        ];

        if ($this->errors) {
            $response['done'] = "fail";
            $response['errors'] = $this->errors;
        }

        return response()->json($response);

    }

    /**
     * Поиск клиента по номеру телефона
     * 
     * @return $this
     */
    public function findClient()
    {

        if (!$this->phone) {
            $this->errors['phone'][] = "Номер телефона клиента не определен";
            return $this;
        }

        $hash = md5($this->phone . env('APP_KEY'));

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

        if (!$this->client)
            return $this;

        if (!$this->myPhone AND !$this->site) {
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

        if ($this->resource AND !$this->source) {
            $this->errors['source'][] = "Источник по ресурсу не определен";
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

        $this->status = Status::find($this->data->status);

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

        $this->status->zeroing_data = json_decode($this->status->zeroing_data);

        return false;

    }

    /**
     * Обнуление заявки по условиям статуса
     * 
     * @return $this
     */
    public function requestZeroing()
    {

        $this->zeroing = true;

        $this->data->delete();
        $this->createNewRequest();

        return $this;

    }

    /**
     * Заполнение поступивших данных и сохранение заявки
     * 
     * @return $this
     */
    public function requestSave()
    {

        if (!$this->data)
            return $this->createNewRequest();

        return $this;

    }

    /**
     * Создание новой заявки
     * 
     * @return $this
     */
    public function createNewRequest()
    {

        $this->data = RequestsRow::create([
            'query_type' => $this->query_type,
            'source_id' => $this->source->id ?? null,
            'sourse_resource' => $this->resource->id ?? null,
        ]);

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

        foreach ($this->request->all() as $key => $row)
            $query_data[$key] = Crypt::encryptString($row);

        $query_data = json_encode($query_data ?? [], JSON_UNESCAPED_UNICODE);

        $data = $this->data
            ? json_encode($this->data, JSON_UNESCAPED_UNICODE)
            : null;

        return IncomingQuery::create([
            'query_data' => $query_data,
            'client_id' => $this->client->id ?? null,
            'request_id' => $this->data->id ?? null,
            'request_data' => $data,
            'ip' => $this->request->ip(),
            'user_agent' => $this->request->header('User-Agent'),
        ]);

    }

    /**
     * Магический метод для вывода несуществующего значения
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name) {

        if (isset($this->$name) === true)
            return $this->$name;

        return null;
        
    }

}
