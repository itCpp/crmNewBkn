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
    public $superadmin = false;

    /**
     * Роль пользователя, дающая полный доступ
     * 
     * @var string
     */
    protected $role;

    /**
     * Все роли, принадлежащие пользователю
     * 
     * @var array
     */
    public $roles = [];

    /**
     * Экземпляр модели пользователя
     * 
     * @var \App\Models\User
     */
    protected $__user;

    /**
     * Уровень роли
     * 
     * @var int|null
     */
    protected $level = null;
    
    /**
     * Создание объекта
     * 
     * @param \App\Models\User $user Экземпляр модели пользователя
     */
    public function __construct($user) {

        // Настройка проверки суперадмина
        $superadmin = (bool) env('USER_SUPER_ADMIN_ACCESS_FOR_ROLE', false);

        // Роль, принадлежащая суперадмину
        $this->role = env('USER_SUPER_ADMIN_ROLE');

        $this->__user = $user; # Экземпляр модели пользователя
        $data = $user->toArray(); # Данные пользователя

        foreach ($data as $name => $value) {
            $this->$name = $value;
        }

        // Определение имени и отчества
        $this->name_io = $this->name ?? "";
        $this->name_io .= " ";
        $this->name_io .= $this->patronymic ?? "";
        $this->name_io = trim($this->name_io);

        // Определение полного ФИО
        $this->name_full = $this->surname ?? "";
        $this->name_full .= " " . $this->name_io;
        $this->name_full = trim($this->name_full);

        // ФИО
        $this->name_fio = preg_replace('~^(\S++)\s++(\S)\S++\s++(\S)\S++$~u', '$1 $2.$3.', $this->name_full);

        // Дата регистрации
        $this->date = date("d.m.Y H:i:s", strtotime($this->created_at));

        // Список ролей, пренаджежащих пользователю  
        foreach ($user->roles as $role)
            $this->roles[] = $role->role;

        // Права суперадмина
        $this->superadmin = in_array($this->role, $this->roles) AND $superadmin;

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

        if ($this->superadmin)
            return true;

        $roles = Role::find($this->roles);

        foreach ($roles as $role) {

            $permissions = $role->permissions()->whereIn('roles_permissions.permission', $permits)->get();

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
     * @return Permissions
     */
    public function getListPermits($permits = []) {

        if (!count($permits))
            return [];

        if ($this->superadmin)
            return $this->superAdminPermitsList($permits);

        $access = [];

        $roles = Role::find($this->roles);

        foreach ($roles as $role) {

            $permissions = $role->permissions()->whereIn('roles_permissions.permission', $permits)->get();

            foreach ($permissions as $permit)
                $access[] = $permit->permission;

        }

        $permissions = $this->__user->permissions()->whereIn('permission', $permits)->get();

        foreach ($permissions as $permit)
            $access[] = $permit->permission;

        foreach ($permits as $permit)
            $list[$permit] = in_array($permit, $access);

        return new Permissions($list ?? []);

    }

    /**
     * Формирование списка разрешений для супер-админа
     * 
     * @param array     $permits Список заправшиваемых разрешений
     * @return Permissions
     */
    protected function superAdminPermitsList($permits) {

        foreach ($permits as $permit) {
            $list[$permit] = true;
        }

        return new Permissions($list ?? []);
        
    }

    /**
     * Определение максимального уровня роли пользователя
     * 
     * @return int
     */
    public function getRoleLevel() {

        if ($this->level)
            return $this->level;

        $this->level = 0;

        foreach (Role::whereIn('role', $this->roles)->get() as $role) {
            $this->level = $role->lvl > $this->level ? $role->lvl : $this->level;
        }

        return $this->level;

    }

    /**
     * Возвращает экземпляр модели пользователя
     * 
     * @return \App\Models\User
     */
    public function getModel() {

        return $this->__user;

    }

    /**
     * Вывод всех вкладок, доступных сотруднику
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getAllTabs()
    {

        if ($this->superadmin)
            return \App\Models\Tab::all();

        $tabs = [];
        $id = [];

        foreach ($this->__user->tabs as $tab) {
            $tabs[] = $tab;
            $id[] = $tab->id;
        }

        foreach ($this->__user->roles as $role) {
            foreach ($role->tabs()->whereNotIn('id', array_unique($id))->get() as $tab) {
                $tabs[] = $tab;
                $id[] = $tab->id;
            }
        }

        return collect($tabs);

    }

    /**
     * Проверка разрешения на вывод данных по вкладке
     * 
     * @param int $id Идентификатор вкладки
     * @return bool
     */
    public function canTab($id = null)
    {

        if ($this->superadmin)
            return true;

        if ($this->__user->tabs()->where('id', $id)->count())
            return true;

        foreach ($this->__user->roles as $role) {
            if ($role->tabs()->where('id', $id)->count())
                return true;
        }

        return false;

    }

    /**
     * Список статусов заявки, доступных для выбора сотруднику
     * 
     * @return collect
     */
    public function getStatusesList()
    {

        if ($this->superadmin)
            return \App\Models\Status::all();

        $statuses = [];
        $id = [];
    
        foreach ($this->__user->roles as $role) {
            foreach ($role->statuses()->whereNotIn('id', array_unique($id))->get() as $row) {
                $statuses[] = $row;
                $id[] = $row->id;
            }
        }

        return collect($statuses ?? []);

    }

}
