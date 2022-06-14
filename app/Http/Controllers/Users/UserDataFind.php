<?php

namespace App\Http\Controllers\Users;

use App\Models\User;

class UserDataFind
{
    /**
     * Модель пользователя
     * 
     * @var \App\Models\User
     */
    protected $__user;

    /**
     * Создание экземпляра объекта
     * 
     * @param  int $user_id
     * @return void
     */
    public function __construct($user_id)
    {
        $this->__user = User::find($user_id);
    }

    /**
     * Выводит объект сотрудника
     * 
     * @return \App\Http\Controllers\Users\UserData
     */
    public function __invoke()
    {
        return new UserData($this->__user);
    }
}
