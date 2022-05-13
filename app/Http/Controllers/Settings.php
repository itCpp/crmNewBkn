<?php

namespace App\Http\Controllers;

use App\Exceptions\Exceptions;
use App\Models\SettingsGlobal;
use Exception;

class Settings
{
    /**
     * Используемые ключи настроек
     * 
     * @var array
     */
    protected $items = [];

    /**
     * Допустимые типы переменных
     * 
     * @var array
     */
    public static $types = [
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
    public function __construct(...$settings)
    {
        // $rows = new SettingsGlobal;

        // if (count($settings))
        //     $rows = $rows->whereIn('id', $settings);

        // try {
        //     $rows = $rows->get();
        // } catch (Exception) {
        //     return null;
        // }

        // foreach ($rows as $row) {

        //     $value = $row->value;
        //     $type = $this->getType($row->type);

        //     settype($value, $type);

        //     $this->{$row->id} = $value;
        // }
    }

    /**
     * Магический метод для вывода несуществующего значения
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (!empty($this->items[$name]))
            return $this->items[$name];

        if ($row = SettingsGlobal::find($name)) {

            $value = $row->value;
            $type = $this->getType($row->type);

            settype($value, $type);

            return $this->items[$name] = $value;
        }

        return $this->items[$name] = null;
    }

    /**
     * Определение типа переменной
     * 
     * @param null|string $type
     * @return string
     */
    public function getType($type = null)
    {
        return in_array($type, self::$types) ? $type : "boolean";
    }

    /**
     * Изменение занчения настройки
     * 
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public static function set($key, $value = null)
    {
        if (!$setting = SettingsGlobal::find($key))
            throw new Exceptions("Настройка {$key} не найдена");

        $settings = new static($key);

        $type = $settings->getType($setting->type);

        if (gettype($value) != $type)
            throw new Exceptions("Передан неправильный тип переменной, должен быть {$type}");

        settype($value, $type);

        $setting->value = $value;
        $setting->save();

        return $value;
    }

    /**
     * Изменяет настройки
     * 
     * @param  string $key
     * @param  mixed $value
     * @return \App\Models\SettingsGlobal
     */
    public static function setOrCreate($key, $value = null)
    {
        if (!$setting = SettingsGlobal::find($key)) {
            $setting = SettingsGlobal::create([
                'id' => $key,
                'type' => gettype($value),
            ]);
        }

        $value = self::set($key, $value);

        return $setting;
    }
}
