<?php

namespace App\Http\Controllers\Requests;

use App\Models\RequestsClient;

trait RequestsQuerySearch
{
    /**
     * Применение поискового запрса
     * 
     * @return \App\Models\RequestsRow
     */
    public function setSearchQuery()
    {
        $this->setSearchId()
            ->setSearchPhone()
            ->setSearchFio()
            ->setSeatchPin()
            ->setUserPermits()
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
    public function setSeatchPin()
    {
        if (!$this->search->pin)
            return $this;

        $this->model = $this->model->where('pin', $this->search->pin);

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
