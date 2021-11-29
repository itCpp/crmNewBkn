<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class Dates
{
    /**
     * Создание экземпляра объекта
     * 
     * @param null|string $start
     * @param null|string $stop
     * @return void
     */
    public function __construct($start = null, $stop = null)
    {
        if ($start) {
            $this->start = $start;
        }

        if ($stop) {
            $this->stop = $stop;
        }
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
}
