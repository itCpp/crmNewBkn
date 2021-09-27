<?php

namespace App\Http\Controllers;

use App\Models\SettingsGlobal;

class Settings
{

    /**
     * Допустимые типы переменных
     * 
     * @var array
     */
    protected $types = [
        "boolean", "bool",
        "integer", "int",
        "float", "double",
        "string",
        "null",
    ];
    
    /**
     * Инициализаяция объекта
     * 
     * @param array $settings Список требуемых настроек
     * @return void
     */
    public function __construct($settings = [])
    {
        
        $rows = new SettingsGlobal;

        if ($settings)
            $rows = $rows->whereIn('name', $settings);

        foreach ($rows->get() as $row) {

            $value = $row->value;
            $type = in_array($row->type, $this->types) ? $row->type : "boolean";

            settype($value, $type);

            $this->{$row->name} = $value;

        }

    }

    /**
     * Магический метод для вывода несуществующего значения
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name) {

        if (isset($this->$name) === true)
            return $this->$name;

        return null;
        
    }

}
