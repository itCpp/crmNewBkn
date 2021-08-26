<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Role;

class UserData extends Controller
{

    /**
     * Настройка проверки супер-админа
     * 
     * @var bool
     */
    protected $superadmin = false;

    /**
     * Роль пользователя, дающая полный доступ
     * 
     * @var string
     */
    protected $role = "developer";

    /**
     * Экземпляр модели пользователя
     * 
     * @var \App\Models\User
     */
    protected $__user;
    
    /**
     * Создание объекта
     * 
     * @param \App\Models\User $user Экземпляр модели пользователя
     */
    public function __construct($user) {

        $this->superadmin = (bool) env('USER_SUPER_ADMIN_ACCESS_FOR_ROLE', false);

        $this->__user = $user;

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

        $this->roles = [];
        
        foreach ($user->roles as $role)
            $this->roles[] = $role->role;

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

    /**
     * Проверка разрешения у пользователя
     * 
     * @param array     $permits Список разрешений к проверке
     * @return bool
     */
    public function can(...$permits) {

        if (in_array($this->role, $this->roles) AND $this->superadmin)
            return true;

        $roles = Role::find($this->roles);

        foreach ($roles as $role) {

            $permissions = $role->permissions()->whereIn('permission', $permits)->get();

            if (count($permissions))
                return true;

        }

        $permissions = $this->__user->permissions()->whereIn('permission', $permits)->get();

        if (count($permissions))
            return true;

        return false;

    }

    /**
     * Вывод списка разрешений пользователя
     * 
     * @param array     $permits Список разрешений к проверке
     * @return array
     */
    public function getListPermits($permits = []) {

        if (!count($permits))
            return [];

        if (in_array($this->role, $this->roles) AND $this->superadmin)
            return $this->superAdminPermitsList($permits);

        $access = [];

        $roles = Role::find($this->roles);

        foreach ($roles as $role) {

            $permissions = $role->permissions()->whereIn('permission', $permits)->get();

            foreach ($permissions as $permit)
                $access[] = $permit->permission;

        }

        $permissions = $this->__user->permissions()->whereIn('permission', $permits)->get();

        foreach ($permissions as $permit)
            $access[] = $permit->permission;

        foreach ($permits as $permit)
            $list[$permit] = in_array($permit, $access);

        return $list ?? [];

    }

    /**
     * Формирование списка разрешений для супер-админа
     * 
     * @param array     $permits Список заправшиваемых разрешений
     * @return array
     */
    protected function superAdminPermitsList($permits) {

        foreach ($permits as $permit) {
            $list[$permit] = true;
        }

        return $list ?? [];
        
    }

}
