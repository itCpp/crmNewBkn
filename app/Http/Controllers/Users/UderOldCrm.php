<?php

namespace App\Http\Controllers\Users;

class UderOldCrm
{
    /**
     * Данные сотруднкиа
     * 
     * @var array
     */
    protected $user = [];

    /**
     * Создание экземпляра объекта
     * 
     * @param array
     * @return void
     */
    public function __construct($data = [])
    {
        $this->data = $data;

        foreach ($data as $key => $value) {
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
     * Вывод данных для канала присутсвия
     * 
     * @return array
     */
    public function toPresenceData()
    {
        return [
            'id' => $this->id,
            'name' => $this->fullName,
            'pin' => $this->pin,
        ];
    }

    /**
     * Выдача идентификтаор пользователя
     * 
     * @return int
     */
    public function getAuthIdentifier()
    {
        return $this->id;
    }

    /**
     * @param array $permits
     * @return boolean
     */
    public function can(...$permits)
    {
        return true;
    }
}
