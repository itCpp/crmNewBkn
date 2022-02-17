<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Создание экземпляра объекта пользователя с пустыми данными
 * Необходим для выполнение методов при внутреннем вызове через команды
 * Пустой пользователь имеет права разработчика, т.е. имеет доступ
 * ко всему функционалу
 */
class DeveloperBot extends Controller
{
    /**
     * Создание экземпляра объекта пользователя
     * 
     * @return \App\Http\Controllers\Users\UserData
     */
    public function __invoke()
    {
        $user = new User;
        $user->role = "developer";

        $data = new UserData($user);
        $data->superadmin = true;
        $data->roles = [$user->role];

        return $data;
    }
}
