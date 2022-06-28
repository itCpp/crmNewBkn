<?php

namespace App\Http\Controllers\Requests\Synhro;

use App\Http\Controllers\Dev\RequestsMergeData;
use App\Http\Controllers\Requests\AddRequestCounterTrait;
use App\Http\Controllers\Requests\RequestChange;
use App\Http\Controllers\Users\UsersMerge;
use App\Models\CrmMka\CrmRequest;
use App\Models\CrmMka\CrmUser;
use App\Models\RequestsClient;
use App\Models\RequestsRow;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class Merge extends RequestsMergeData
{
    use AddRequestCounterTrait;

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
     * Инициализация объекта
     * 
     * @return void
     */
    public function __constrict()
    {
        $this->sourceToSource = $this->sourceToSource();
        $this->sourceResourceToRecource = $this->sourceResourceToRecource();
        $this->stateToStatusId = $this->stateToStatusId();
    }

    /**
     * Создание новой заявки
     * 
     * @param  \Illuminate\Http\Request $request
     * @return 
     */
    public function createOrUpdateRequestFromOld(Request $request)
    {
        /** Экземпляр модели старой заявки */
        $row = $this->getCrmRequestRow($request->row);

        $new = RequestsRow::withoutTrashed()->where('id', $row->id)->first();

        if (!$new) {
            $new = new RequestsRow;
            $new->id = $row->id;
        }

        $new->query_type = $this->getQueryType($row);
        $new->callcenter_sector = $this->getSectorId($row);
        $new->pin = (int) $row->pin > 0 ? $row->pin : null;
        $new->source_id = $this->getSourceId($row);
        $new->sourse_resource = $this->getResourceId($row);
        $new->client_name = (bool) $row->name ? $row->name : null;
        $new->theme = (bool) $row->theme ? $row->theme : null;
        $new->region = ((bool) $row->region and $row->region != "Неизвестно") ? $row->region : null;
        $new->check_moscow = $new->region ? RequestChange::checkRegion($new->region) : null;
        $new->comment = (bool) $row->comment ? $row->comment : null;
        $new->comment_urist = (bool) $row->uristComment ? $row->uristComment : null;
        $new->comment_first = (bool) $row->first_comment ? $row->first_comment : null;
        $new->address = $row->address ?: null;
        $new->status_id = $this->getStatusIdFromString($row->state);
        $new->uplift = 1;
        $new->event_at = $this->getEventAt($row);
        $new->created_at = $this->getCreatedAt($row);
        $new->uplift_at = $this->getUpliftAt($row, $new);
        $new->deleted_at = $this->getDeletedAt($row, $new);

        $new->save();

        /** Номера телефонов в старой заявке */
        $phones = $this->getPhones($row);

        $attached = $new->clients()->get()->map(function ($row) {
            return $row->hash;
        })->toArray();

        /** Поиск или создание клиентов по номерам */
        foreach ($this->chenckOrCreteClients($phones) as $client) {

            if (in_array($client->hash, $attached))
                continue;

            $client->requests()->attach($new->id);
        }

        return $new;
    }

    /**
     * Создание экземпляра модели заявки на основе старых данных
     * 
     * @param  array $data
     * @return \App\Models\CrmRequest
     */
    public function getCrmRequestRow($data)
    {
        $row = new CrmRequest;

        foreach ($data as $key => $value) {
            $row->$key = $value;
        }

        return $row;
    }

    /**
     * Проверка и создание клиентов
     * 
     * @param  array $phones
     * @return array
     */
    public function chenckOrCreteClients($phones)
    {
        foreach ($phones as $phone) {

            $hash = $this->hashPhone($phone);

            $clients[] = RequestsClient::firstOrCreate(
                ['hash' => $hash],
                ['phone' => Crypt::encryptString($phone)]
            );
        }

        return $clients ?? [];
    }

    /**
     * Определение идентификатора статуса заявки
     * 
     * @param  string $state
     * @return null|int
     */
    public function getStatusIdFromString($state)
    {
        return $this->stateToStatusId[$state] ?? null;
    }

    /**
     * Проверяет наличие сотрудника или создает его
     * 
     * @param  string $pin
     * @return int
     */
    public function getOperatorUserId($pin)
    {
        if ($user = User::wherePin($pin)->first())
            return $user->id;

        $old = CrmUser::wherePin($pin)->first();

        $user = (new UsersMerge)->createUser($old);
        
        return $user->id ?? null;
    }
}
