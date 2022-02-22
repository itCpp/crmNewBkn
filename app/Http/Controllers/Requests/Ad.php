<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Models\IncomingCallsToSource;
use App\Models\IncomingQuery;
use App\Models\RequestsRow;
use App\Models\RequestsSource;
use App\Models\RequestsSourcesResource;
use Illuminate\Http\Request;

class Ad extends Controller
{
    /**
     * Список расшифрованных номеров
     * 
     * @var array
     */
    protected $phones = [];

    /**
     * Список проверенных источников
     * 
     * @var array
     */
    protected $sources = [];

    /**
     * Список проверенных рекламных источников по ресурсам
     * 
     * @var array
     */
    protected $resources = [];

    /**
     * Данные для модального окна обращений по рекламе
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        if (!$row = RequestsRow::find($request->id))
            return response()->json(['message' => "Заявка не найдена"], 400);

        $this->show_phones = $request->user()->can('clients_show_phone');

        $phones = $row->clients->map(function ($row) {
            return $this->hashPhone($this->decrypt($row->phone));
        });

        $queries = IncomingQuery::where('request_id', $row->id)->count();

        return response()->json([
            'queries' => $queries,
            'calls' => $this->getCallsInfo($phones),
            'texts' => $this->getTextsInfo($phones),
            'ips' => $this->getIpsInfo($row->id),
            'counts' => [
                'ips' => $this->getIpsInfo($row->id, true),
            ],
        ]);
    }

    /**
     * Информация о звонках
     * 
     * @param array $phones
     * @return array
     */
    public function getCallsInfo($phones)
    {
        return IncomingQuery::whereIn('hash_phone', $phones)
            ->where('type', 'call')
            ->orderBy('id', 'DESC')
            ->get()
            ->map(function ($row) {

                $resource = $row->hash_phone_resource ? $this->decryptPhone(
                    $row->hash_phone_resource,
                    $row->query_data->myPhone ?? null
                ) : null;

                $phone = $this->decryptPhone($row->hash_phone, $row->query_data->phone ?? null);

                return [
                    'ad_source' => $row->ad_source ?: $this->findAdSource($resource),
                    'date' => $row->created_at,
                    'manual' => $row->query_data->manual ?? null,
                    'phone' => $this->displayPhoneNumber($phone, $this->show_phones, 2),
                    'resource' => $this->displayPhoneNumber($resource, $this->show_phones, 2),
                    'source' => $this->getSourceData($row->request_data->source_id ?? null),
                ];
            })
            ->toArray();
    }

    /**
     * Расшифровка номера телефона
     * 
     * @param string $hash
     * @param string $phone
     * @return string
     */
    public function decryptPhone($hash, $phone)
    {
        if (!empty($this->phones[$hash]))
            return $this->phones[$hash];

        $phone = $this->decrypt($phone);

        return $this->phones[$hash] = $this->checkPhone($phone) ?: $phone;
    }

    /**
     * Поиск идентификатор рекламы по ресурсу источника
     * 
     * @param string|null $resource
     * @return string|null
     */
    public function findAdSource($resource)
    {
        if (!$resource)
            return null;

        if (!empty($this->resources[$resource]))
            $this->resources[$resource];

        $this->resources[$resource] = null;

        if ($source = IncomingCallsToSource::where('phone', $resource)->first())
            $this->resources[$resource] = $source->ad_place;

        return $this->resources[$resource];
    }

    /**
     * Поиск источников
     * 
     * @param null|int $id
     * @return null|array
     */
    public function getSourceData($id)
    {
        if (!$id)
            return null;

        if (!empty($this->sources[$id]))
            return $this->sources[$id];

        if (!$source = RequestsSource::find($id))
            return $this->sources[$id] = null;

        return $this->sources[$id] = $source->only('id', 'name');
    }

    /**
     * Поиск источников
     * 
     * @param null|int $id
     * @return null|array
     */
    public function getSourceResourceData($id)
    {
        if (!$id)
            return null;

        if (!empty($this->resources[$id]))
            return $this->resources[$id];

        if (!$source = RequestsSourcesResource::find($id))
            return $this->resources[$id] = null;

        return $this->resources[$id] = $source->only('id', 'val', 'type');
    }

    /**
     * Информация о тестовых заявках
     * 
     * @param array $phones
     * @return array
     */
    public function getTextsInfo($phones)
    {
        return IncomingQuery::whereIn('hash_phone', $phones)
            ->where('type', 'text')
            ->orderBy('id', 'DESC')
            ->get()
            ->map(function ($row) {

                $phone = $this->decryptPhone($row->hash_phone, $row->query_data->phone ?? null);

                return [
                    'ad_source' => $row->ad_source,
                    'date' => $row->created_at,
                    'manual' => $row->query_data->manual ?? null,
                    'phone' => $this->displayPhoneNumber($phone, $this->show_phones, 2),
                    'sourse_resource' => $this->getSourceResourceData($row->request_data->sourse_resource ?? null),
                    'source' => $this->getSourceData($row->request_data->source_id ?? null),
                    'query' => collect((array) $row->query_data ?? [])->only(
                        'page',
                        'site',
                        'device',
                        'region',
                        'comment',
                        'client_name',
                        'utm_term',
                        'utm_medium',
                        'utm_source',
                        'utm_content',
                        'utm_campaign'
                    ),
                ];
            })
            ->toArray();
    }

    /**
     * Информация об IP адресах
     * 
     * Антон Суханов, [17.11.2021 17:56]
     * [ Фотография ]
     * вот тут изменения, выводиться должен ip адрес, выборка его идет не через телефон
     * а конкретная заявка ip и аты с выводом предыдущих этого же ip`
     *
     * @param int $id
     * @param boolean $count Флаг вывода счетчика
     * @return array|int
     */
    public function getIpsInfo($id, $count = false)
    {
        $ips = IncomingQuery::select('ip')
            ->where([
                ['ip', '!=', null],
                ['request_id', $id],
            ])
            ->distinct()
            ->get()
            ->map(function ($row) {
                return $row->ip;
            });

        $data = IncomingQuery::whereIn('ip', $ips)->orderBy('id', 'DESC');

        if ($count)
            return $data->count();

        return $data->limit(100)->get()->map(function ($row) {

            $phone = $this->decryptPhone($row->hash_phone, $row->query_data->phone ?? null);

            return [
                'id' => $row->request_id,
                'ip' => $row->ip,
                'ad_source' => $row->ad_source,
                'date' => $row->created_at,
                'manual' => $row->query_data->manual ?? null,
                'phone' => $this->displayPhoneNumber($phone, $this->show_phones, 2),
                'sourse_resource' => $this->getSourceResourceData($row->request_data->sourse_resource ?? null),
                'source' => $this->getSourceData($row->request_data->source_id ?? null),
                'query' => collect((array) $row->query_data ?? [])->only(
                    'page',
                    'site',
                    'device',
                    'region',
                    'comment',
                    'client_name',
                    'utm_term',
                    'utm_medium',
                    'utm_source',
                    'utm_content',
                    'utm_campaign'
                ),
            ];
        })->toArray();
    }
}
