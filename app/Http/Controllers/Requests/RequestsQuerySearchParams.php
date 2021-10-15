<?php

namespace App\Http\Controllers\Requests;

class RequestsQuerySearchParams
{
    /**
     * Массив ключей поискового запроса
     * 
     * @var array
     */
    protected $queryKeys = [];

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

            if ($value and $value != "") {

                $this->$key = $value;

                $this->queryKeys[] = $key;
            }
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

    /**
     * Вывод количества найденных ключей
     * 
     * @return int
     */
    public function getQueryKeysCount()
    {
        return count($this->queryKeys);
    }

    /**
     * Вывод массива ключей
     * 
     * @return int
     */
    public function getQueryKeys()
    {
        return $this->queryKeys;
    }
}
