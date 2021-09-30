<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\CrmMka\CrmUser;
use App\Models\User;
use Illuminate\Http\Request;

/** 
 * Black 0;30
 * Blue 0;34
 * Green 0;32
 * Cyan 0;36
 * Red 0;31
 * Purple 0;35
 * Brown 0;33
 * Light Gray 0;37 
 * Dark Gray 1;30
 * Light Blue 1;34
 * Light Green 1;32
 * Light Cyan 1;36
 * Light Red 1;31
 * Light Purple 1;35
 * Yellow 1;33
 * White 1;37
 */
class UsersMerge extends Controller
{

    /**
     * Начало новых пинов для сектора
     * 
     * @var array
     */
    protected $sectorsPin = [
        3 => 30000,
        4 => 30000,
        5 => 30000,
        6 => 30000,
        7 => 40000
    ];

    /**
     * Сопосталение старых секторов с новыми
     * 
     * @var array
     */
    protected $oldSectors = [
        3 => [1, 1],
        4 => [1, 1],
        5 => [1, 1],
        6 => [1, 1],
        7 => [2, 1],
    ];

    /**
     * Список ролей для сотрудника сектора
     * 
     * @var array
     */
    protected $roles = [
        3 => ['caller'],
        4 => ['caller'],
        5 => ['caller'],
        6 => ['caller'],
        7 => ['caller', 'caller_che'],
    ];

    /**
     * Начало процесса
     * 
     * @return null
     */
    public function start()
    {

        // Формирование руководителей, сисадминов и тд
        $users = CrmUser::where('state', 'Работает')
            ->whereIn('rights', ['admin', 'nachColl'])
            ->orderBy('pin')
            ->get();

        echo "Разработчики, руководители и тд...\n\033[0m";

        foreach ($users as $user) {
            $new = $this->createUser($user, "secret");
        }

        // Формирование админов секторов
        $users = CrmUser::where('state', 'Работает')
            ->whereIn('rights', ['nachCollSamara'])
            ->orderBy('pin')
            ->get();

        echo "Админы\n\033[0m";

        foreach ($users as $user) {
            $new = $this->createUser($user, "secret");
        }

        // Формирование кольщиков
        $users = CrmUser::where('state', 'Работает')
            ->whereIn('rights', ['caller'])
            ->orderBy('pin')
            ->get();

        echo "Кольщики\n\033[0m";

        foreach ($users as $user) {
            $new = $this->createUser($user);
        }

        return null;
    }

    /**
     * Создание строки пользователя
     * 
     * @param \App\Models\CrmMka\CrmUser $user
     * @param string $auth
     */
    public function createUser($user, $auth = "admin")
    {

        // Определение пина
        $max = null;
        $pinStart = $this->sectorsPin[$user->{'call-center'}] ?? null;

        if ($pinStart)
            $max = User::whereBetween('pin', [$pinStart, $pinStart + 9999])->max('pin');

        if ($pinStart and $max)
            $pin = $max + 1;
        elseif ($pinStart)
            $pin = $pinStart;
        else
            $pin = $user->pin;

        if (in_array($user->pin, [890, 866, 813]))
            $pin = $user->pin;

        if ($user->pin == 813)
            $user->username = "abrik";

        // ФИО
        $user->fullName = preg_replace('/\s/', ' ', $user->fullName);
        $fio = explode(" ", $user->fullName);

        $create = [
            'pin' => $pin,
            'old_pin' => $pin == $user->pin ? null : $user->pin,
            'surname' => $fio[0] ?? null,
            'name' =>  $fio[1] ?? null,
            'patronymic' =>  $fio[2] ?? null,
            'created_at' => $user->reg_date,
            'auth_type' => $auth,
            'password' => "old|" . $user->password,
            'login' => $user->username,
            'callcenter_id' => $this->oldSectors[$user->{'call-center'}][0] ?? null,
            'callcenter_sector_id' => $this->oldSectors[$user->{'call-center'}][1] ?? null,
        ];

        try {

            $new = User::create($create);
            echo "\033[32m";

            // Роли сотрудника
            if (isset($new) AND $roles = $this->roles[$user->{'call-center'}] ?? null) {

                foreach ($roles as $role) {
                    $new->roles()->attach($role);
                    echo "\t\tAdd role {$role}\n";
                }

            }

        } catch (\Illuminate\Database\QueryException) {
            echo "\033[31m";
        }

        echo "\t{$user->pin} {$user->username} {$user->fullName}\n\033[0m";

        return $new ?? null;
    }
}
