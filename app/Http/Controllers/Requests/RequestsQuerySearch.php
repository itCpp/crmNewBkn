<?php

namespace App\Http\Controllers\Requests;

use App\Exceptions\CreateRequestsSqlQuery;
use App\Models\RequestsClient;
use Illuminate\Support\Str;

trait RequestsQuerySearch
{
    /**
     * Применение поискового запрса
     * 
     * @return \App\Models\RequestsRow
     */
    public function setSearchQuery()
    {
        if (is_object($this->search)) {

            foreach ($this->search as $id => $value) {
                $method = "setSearch" . Str::studly($id);
                $this->$method();
            }
        }

        $this->setUserPermits()
            ->setUserPermitsFilter()
            ->orderBy();

        return $this->model;
    }

    /**
     * Поиск по индентификатору
     * 
     * @return $this
     */
    public function setSearchId()
    {
        if (!$this->search->id)
            return $this;

        $this->model = $this->model->where('id', $this->search->id);

        return $this;
    }

    /**
     * Поиск по индентификатору
     * 
     * @return $this
     */
    public function setSearchPhone()
    {
        if (!$this->search->phone)
            return $this;

        $ids = [];

        if ($phone = $this->checkPhone($this->search->phone)) {

            $hash = AddRequest::getHashPhone($phone);
            $clients = RequestsClient::where('hash', $hash)->get();

            foreach ($clients as $client) {
                foreach ($client->requests as $row) {
                    $ids[] = $row->id;
                }
            }
        }

        $this->model = $this->model->whereIn('id', array_unique($ids));

        return $this;
    }

    /**
     * Поиск по индентификатору
     * 
     * @return $this
     */
    public function setSearchFio()
    {
        if (!$this->search->fio)
            return $this;

        $this->model = $this->model->where('client_name', 'LIKE', "%{$this->search->fio}%");

        return $this;
    }

    /**
     * Поиск по индентификатору
     * 
     * @return $this
     */
    public function setSearchPin()
    {
        if (!$this->search->pin)
            return $this;

        $this->model = $this->model->where('pin', $this->search->pin);

        return $this;
    }

    /**
     * Поиск по региону
     * 
     * @return $this
     */
    public function setSearchRegion()
    {
        if (!$this->search->region)
            return $this;

        $this->model = $this->model->where('region', $this->search->region);

        return $this;
    }

    /**
     * Поиск по тематике
     * 
     * @return $this
     */
    public function setSearchTheme()
    {
        if (!$this->search->theme)
            return $this;

        $this->model = $this->model->where('theme', $this->search->theme);

        return $this;
    }

    /**
     * Поиск по источнику
     * 
     * @return $this
     */
    public function setSearchSource()
    {
        if (!$this->search->source)
            return $this;

        $this->model = $this->model->where('source_id', $this->search->source);

        return $this;
    }

    /**
     * Поиск по статусу
     * 
     * @return $this
     */
    public function setSearchStatus()
    {
        if (!$this->search->status)
            return $this;

        if ((int) $this->search->status < 0)
            $this->search->status = null;

        $this->model = $this->model->where('status_id', $this->search->status);

        return $this;
    }

    /**
     * Применение прав доступа к заявкам
     * 
     * @return $this
     */
    public function setUserPermits()
    {
        $sector = $this->user->checkedPermits()->requests_all_my_sector;
        $sectors = $this->user->checkedPermits()->requests_all_sectors;
        $callcenters = $this->user->checkedPermits()->requests_all_callcenters;

        if ($callcenters)
            return $this;

        if ($sectors) {
            $this->model = $this->model->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereIn('callcenter_sector', $this->user->getAllSectors())
                        ->whereNotNull('callcenter_sector');
                })
                    ->orWhere('pin', $this->user->pin);
            });

            return $this;
        }

        if ($sector) {
            $this->model = $this->model->where(function ($query) {
                $query->where([
                    ['callcenter_sector', $this->user->callcenter_sector_id],
                    ['callcenter_sector', '!=', null],
                ])
                    ->orWhere('pin', $this->user->pin);
            });

            return $this;
        }

        $this->model = $this->model->where('pin', $this->user->pin);

        return $this;
    }

    /**
     * Применение сортировки
     * 
     * @return $this
     */
    public function orderBy()
    {
        $this->models = $this->model->orderBy('id');

        return $this;
    }
}
