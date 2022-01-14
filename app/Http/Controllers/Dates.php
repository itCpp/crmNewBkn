<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;

/**
 * Подготавливает набор дат
 * 
 * @property    string              $start          Дата начала
 * @property    string              $stop           Дата окончания
 * @property    string              $startPeriod    Дата начала периода
 * @property    string              $stopPeriod     Дата окончания периода
 * @property    string              $startMonth     Дата начала месяца
 * @property    string              $stopMonth      Дата окончания месяца
 * @property    int                 $diff           Количество дней в периоде
 * @property    array<string>       $days           Масcив дат выбранного периода
 */
class Dates
{
    /**
     * Дата начала
     * 
     * @var \Carbon\Carbon
     */
    protected $first;

    /**
     * Дата окончания
     * 
     * @var \Carbon\Carbon
     */
    protected $second;

    /**
     * Создание экземпляра объекта
     * 
     * @param null|string $start
     * @param null|string $stop
     * @param null|string $type Тип формирования даты
     *      * periodNow Диапазон текущего двухнедельного периода
     *      * periodPrev Диапазон предыдщуго периода
     *      * periodNext Диапазон следующего период
     * @return void
     */
    public function __construct($start = null, $stop = null, $type = null)
    {
        $this->first = $start ? Carbon::create($start) : Carbon::now();
        $this->second = $stop ? Carbon::create($stop) : Carbon::now();

        if ($this->first > $this->second)
            $this->second = $this->first->copy();

        $this->setDates($type);

        $this->type = $type;
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
     * Подготавливает набор дат
     * 
     * @param null|string $type
     * @return null
     */
    protected function setDates($type)
    {
        if ($type)
            $this->setTypeDates($type);

        if ($this->first->day >= 16) {
            $this->startPeriod = $this->first->copy()->format("Y-m-16");
        } else {
            $this->startPeriod = $this->first->copy()->format("Y-m-01");
        }

        if ($this->second->day >= 16) {
            $this->stopPeriod = $this->second->copy()->format("Y-m-t");
        } else {
            $this->stopPeriod = $this->second->copy()->format("Y-m-15");
        }

        $this->startMonth = $this->first->copy()->format("Y-m-01");
        $this->stopMonth = $this->first->copy()->format("Y-m-t");

        $this->start = $this->first->format("Y-m-d");
        $this->stop = $this->second->format("Y-m-d");

        $this->diff = $this->first->diffInDays($this->second);

        $this->findDays();

        return null;
    }

    /**
     * Устанавливает тип диапазона даты по умолчанию
     * В публичных свойствах будут установлены соответствующие даты
     * 
     * @param string $type
     * @return null
     */
    protected function setTypeDates($type)
    {
        if ($type == "periodNow")
            $this->setNowPeriod();
        else if ($type == "periodNext")
            $this->setNextPeriod();
        else if ($type == "periodPrev")
            $this->setPrevPeriod();

        return null;
    }

    /**
     * Установка дат с текущим периодом
     * 
     * @return $this
     */
    protected function setNowPeriod()
    {
        if ($this->first->day >= 16) {
            $this->first->setDay(16);
        } else {
            $this->first->setDay(1);
        }

        if ($this->second->day >= 16) {
            $this->second->endOfMonth();
        } else {
            $this->second->setDay(15);
        }

        return $this;
    }

    /**
     * Установка дат следующего периода
     * 
     * @return $this
     */
    protected function setNextPeriod()
    {
        if ($this->first->day >= 16) {
            $this->first->addMonth()->setDay(1);
        } else {
            $this->first->setDay(16);
        }

        $this->second = $this->first->copy();

        return $this->setNowPeriod();
    }

    /**
     * Установка дат предыдущего периода
     * 
     * @return $this
     */
    protected function setPrevPeriod()
    {
        if ($this->first->day >= 16) {
            $this->first->setDay(1);
        } else {
            $this->first->subMonth()->endOfMonth();
        }

        $this->second = $this->first->copy();

        return $this->setNowPeriod();
    }

    /**
     * Поиск дат в выбранном периоде
     * 
     * @return $this
     */
    public function findDays()
    {
        $this->days = [];

        for ($date = $this->first; $date->lte($this->second); $date->addDay()) {
            $this->days[] = $date->format('Y-m-d');
        }

        return $this;
    }
}
