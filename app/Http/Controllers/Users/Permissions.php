<?php

namespace App\Http\Controllers\Users;

class Permissions
{

    /**
     * Инициализация объекта
     * 
     * @return \App\Http\Controllers\Users\Permissions
     */
    public function __construct($list = [])
    {
        foreach ($list as $key => $value) {
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
        if (isset($this->$name) === true)
            return $this->$name;

        return null;
    }

    /**
     * Добавление разрешений в список проверенных
     * 
     * @param array
     */
    public function appends($list = [])
    {
        foreach ($list as $key => $value) {
            $this->$key = $value;
        }
    }
}
