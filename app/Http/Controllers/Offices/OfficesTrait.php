<?php

namespace App\Http\Controllers\Offices;

use App\Models\Office;

trait OfficesTrait
{
    /**
     * Массив проверенных офисов
     * 
     * @var array
     */
    protected $get_office_name = [];

    /**
     * Выводт все активные офисы
     * 
     * @return array
     */
    public function getActiveOffices()
    {
        if (!empty($this->get_active_offices))
            return $this->get_active_offices;

        return $this->get_active_offices = Office::where('active', 1)
            ->orderBy('name')
            ->get()
            ->map(function ($row) {

                $this->get_office_name[$row->id] = $row->name;

                return $row->only('id', 'name');
            })
            ->toArray();
    }

    /**
     * Определяет наименование офиса по идентификатору
     * 
     * @param  int $addr
     * @return null|string
     */
    public function getOfficeName($addr)
    {
        if (isset($this->get_office_name[$addr]))
            return $this->get_office_name[$addr];

        $office = Office::find($addr);

        return $this->get_office_name[$addr] = $office->name ?? null;
    }
}
