<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Models\IncomingCallsToSource;
use App\Models\IncomingQuery;
use App\Models\RequestsRow;
use App\Models\RequestsSource;
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

        // $show_phones = $request->user()->can('clients_show_phone');

        $phones = $row->clients->map(function ($row) {
            return $this->hashPhone($this->decrypt($row->phone));
        });

        $queries = IncomingQuery::where('request_id', $row->id)->count();

        return response()->json([
            'queries' => $queries,
            'calls' => $this->getCallsInfo($phones),
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
                    'phone' => $phone,
                    'resource' => $resource,
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
}
