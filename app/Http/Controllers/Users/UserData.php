<?php

namespace App\Http\Controllers\Users;

use App\Models\CallcenterSector;
use App\Models\RequestsSource;
use App\Models\Role;
use App\Models\Status;
use App\Models\Tab;
use App\Models\User;
use App\Models\UserSetting;

/**
 * @property null|string auth_type              Тип авторизации
 * @property null|int callcenter_id             Идентификтаор колл-центра
 * @property null|int callcenter_sector_id      Идентификтаор сектора
 * @property string created_at                  Дата создания "2019-05-21T21:00:00.000000Z"
 * @property string date                        Дата создания "22.05.2019 00:00:00"
 * @property null|string deleted_at             Дата удаления сотрудника
 * @property int id                             Идентификатор сотрудника
 * @property string login                       Логин сотрудника
 * @property null|string name                   Имя сотруднкиа
 * @property string name_fio                    ФИО сотрудника "Иванов И.И."
 * @property string name_full                   Полное ФИО сотрудника
 * @property string name_io                     Полное имя и отчество сотрудника
 * @property null|string old_pin                Старый персональный номер
 * @property null|string patronymic             Отчество сотрудника
 * @property int pin                            Персональный номер сотрудника
 * @property null|int position_id               Идентификатор должности
 * @property array<string> roles                Список ролей сотрудника
 * @property bool superadmin                    Флаг суперадмина (полный доступ)
 * @property string surname                     Фамилия сотрудника
 * @property null|string telegram_id            Идентификатор Телеграма
 * @property string updated_at                  Дата и время обновления
 */
class UserData
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
     * Список проверенных разрешений
     * 
     * @var \App\Http\Controllers\Users\Permissions
     */
    protected $__permissions;

    /**
     * Массив идентификаторов всех секторов колл-центра сотрудника
     * 
     * @var array
     */
    protected $allSectors = [];

    /**
     * Создание объекта
     * 
     * @param  \App\Models\User $user Экземпляр модели пользователя
     * @return void
     */
    public function __construct(User $user)
    {
        $this->__permissions = new Permissions;

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
        $this->name_io = $this->createNameIo($this->name, $this->patronymic);

        // Определение полного ФИО
        $this->name_full = $this->createNameFull($this->surname, $this->name, $this->patronymic);

        // ФИО
        $this->name_fio = $this->createNameFio($this->surname, $this->name, $this->patronymic);

        // Дата регистрации
        $this->date = date("d.m.Y H:i:s", strtotime($this->created_at));

        $this->roles = [];

        // Список ролей, пренаджежащих пользователю  
        foreach ($user->roles as $role)
            $this->roles[] = $role->role;

        // Права суперадмина
        $this->superadmin = in_array($this->role, $this->roles) and $superadmin;
    }

    /**
     * Магический метод для вывода несуществующего значения
     * 
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->$name) === true)
            return $this->$name;

        if ($name == "settings") {
            return $this->settings = UserSetting::firstOrCreate([
                'user_id' => $this->__user->id
            ]);
        }

        return null;
    }

    /**
     * Формирование имени и отчества
     * 
     * @param  string $name Имя
     * @param  string $patronymic Отчество
     * @return string
     */
    public static function createNameIo($name, $patronymic)
    {
        $name_io = $name ?? "";
        $name_io .= " ";
        $name_io .= $patronymic ?? "";

        return trim($name_io);
    }

    /**
     * Формирование полного фио
     * 
     * @param  string $surname Фамилия
     * @param  string $name Имя
     * @param  string $patronymic Отчество
     * @return string
     */
    public static function createNameFull($surname, $name, $patronymic)
    {
        $name_full = $surname ?? "";
        $name_full .= " " . self::createNameIo($name, $patronymic);

        return trim($name_full);
    }

    /**
     * Формирование сокращенного фио
     * 
     * @param  string $surname Фамилия
     * @param  string $name Имя
     * @param  string $patronymic Отчество
     * @return string
     */
    public static function createNameFio($surname, $name, $patronymic)
    {
        return preg_replace(
            '~^(\S++)\s++(\S)\S++\s++(\S)\S++$~u',
            '$1 $2.$3.',
            self::createNameFull($surname, $name, $patronymic)
        );
    }

    /**
     * Метод записи события рабочего веремни сотрудника
     * 
     * @param  string $type
     * @return \App\Models\UserWorkTime
     */
    public function writeWorkTime($type)
    {
        return Worktime::writeEvent($this->pin, $type);
    }

    /**
     * Проверка разрешения у пользователя
     * 
     * @param  array $permits Список разрешений к проверке
     * @return bool
     */
    public function can(...$permits)
    {
        if ($this->superadmin)
            return true;

        foreach ($permits as $permit) {
            if ($this->__permissions->$permit)
                return true;
        }

        foreach ($this->__user->roles()->get() as $role) {

            $role->permissions()
                ->whereIn('roles_permissions.permission', $permits)
                ->get()
                ->each(function ($row) use (&$checkeds) {
                    $checkeds[] = $row->permission;
                });
        }

        $this->__user->permissions()
            ->whereIn('permission', $permits)
            ->get()
            ->each(function ($row) use (&$checkeds) {
                $checkeds[] = $row->permission;
            });

        $checkeds = array_values(array_unique($checkeds ?? []));

        foreach ($permits as $permit)
            $appends[$permit] = in_array($permit, $checkeds);

        $this->__permissions->appends($appends ?? []);

        foreach ($permits as $permit)
            if ($this->__permissions->$permit)
                return true;

        return false;
    }

    /**
     * Вывод списка разрешений пользователя
     * 
     * @param  array $permits Список разрешений к проверке
     * @return \App\Http\Controllers\Users\Permissions
     */
    public function getListPermits($permits = [])
    {
        if (!count($permits))
            return $this->__permissions;

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

        $this->__permissions->appends($list ?? []);

        return $this->__permissions;
    }

    /**
     * Вывод уже проверенных разрешений
     * 
     * @return \App\Http\Controllers\Users\Permissions
     */
    public function checkedPermits()
    {
        return $this->__permissions;
    }

    /**
     * Формирование списка разрешений для супер-админа
     * 
     * @param  array $permits Список заправшиваемых разрешений
     * @return \App\Http\Controllers\Users\Permissions
     */
    protected function superAdminPermitsList($permits)
    {
        foreach ($permits as $permit) {
            $list[$permit] = true;
        }

        $this->__permissions->appends($list ?? []);

        return $this->__permissions;
    }

    /**
     * Определение максимального уровня роли пользователя
     * 
     * @return int
     */
    public function getRoleLevel()
    {
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
    public function getModel()
    {
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
            return Tab::orderBy('position')->get();

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

        usort($tabs, function ($a, $b) {
            return (int) $a->position - (int) $b->position;
        });

        return collect($tabs);
    }

    /**
     * Проверка разрешения на вывод данных по вкладке
     * 
     * @param  int $id Идентификатор вкладки
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
     * @return \Illuminate\Support\Collection
     */
    public function getStatusesList()
    {
        if ($this->superadmin)
            return Status::all();

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

    /**
     * Выдача идентификтаор пользователя
     * 
     * @return int|null
     */
    public function getAuthIdentifier()
    {
        return $this->id;
    }

    /**
     * Данные для канала присутствия
     * 
     * @return array
     */
    public function toPresenceData()
    {
        return [
            'id' => $this->id,
            'pin' => $this->pin,
            'name' => $this->name_full,
            'fio' => $this->name_fio,
            'callcenter' => $this->callcenter_id,
            'sector' => $this->callcenter_sector_id,
        ];
    }

    /**
     * Вывод всех секторов коллцентра сотрудника
     * 
     * @return array
     */
    public function getAllSectors()
    {
        if (count($this->allSectors))
            return $this->allSectors;

        $this->allSectors = CallcenterSector::where('callcenter_id', $this->callcenter_id)
            ->get()
            ->map(function ($row) {
                return $row->id;
            })
            ->toArray();

        return $this->allSectors;
    }

    /**
     * Вывод наименования сектора острудника
     * 
     * @return null|string
     */
    public function getSectorName()
    {
        return CallcenterSector::find($this->callcenter_id)->name ?? null;
    }

    /**
     * Выводит источники, доступные сотруднику
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getSourceList()
    {
        if (!empty($this->source_list))
            return $this->source_list;

        if ($this->superadmin)
            return RequestsSource::orderBy('name')->get();

        $ids = [];
        $sources = [];

        foreach (($this->__user->roles ?? []) as $role) {

            $role->sources->each(function ($source) use (&$sources, &$ids) {

                if (in_array($source->id, $ids))
                    return;

                $ids[] = $source->id;
                $sources[] = $source;
            });
        }

        return $this->source_list = collect($sources)->sortBy('name')->values();
    }
}
