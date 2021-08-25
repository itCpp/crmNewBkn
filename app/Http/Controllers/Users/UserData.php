<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserData extends Controller
{
    
    /**
     * Создание объекта
     * 
     * @param \App\Models\User $user Экземпляр модели пользователя
     */
    public function __construct($user) {

        $data = $user->toArray();

        foreach ($data as $name => $value) {
            $this->$name = $value;
        }

        $this->name_io = $this->name ?? "";
        $this->name_io .= " ";
        $this->name_io .= $this->patronymic ?? "";
        $this->name_io = trim($this->name_io);

        $this->name_full = $this->surname ?? "";
        $this->name_full .= " " . $this->name_io;
        $this->name_full = trim($this->name_full);

        $this->name_fio = preg_replace('~^(\S++)\s++(\S)\S++\s++(\S)\S++$~u', '$1 $2.$3.', $this->name_full);

        $this->roles = $user->roles;

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
