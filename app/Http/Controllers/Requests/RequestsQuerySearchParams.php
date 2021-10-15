<?php

namespace App\Http\Controllers\Requests;

class RequestsQuerySearchParams
{
    /**
     * Объявление объекта
     * 
     * @param array|null $params
     * @return void
     */
    public function __construct($params = [])
    {
        if (!is_array($params))
            return $this;

        foreach ($params as $key => $value) {
            $this->$key = $value;
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
        if (isset($this->$name) === true) {

            if ($this->$name == "")
                return null;

            return $this->$name;
        }

        return null;
    }
}
