<?php

namespace App\Http\Controllers\Users;

use App\Exceptions\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Models\CrmMka\CrmUser;
use App\Models\User;
use App\Models\UsersPositionsStory;
use Illuminate\Http\Request;

class UsersMerge extends Controller
{
    /**
     * Начало новых пинов для сектора
     * 
     * @var array
     */
    protected $sectorsPin = [
        2 => 20000,
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
        1 => [1, 1],
        2 => [1, 1],
        3 => [2, 2],
        4 => [2, 2],
        5 => [2, 2],
        6 => [2, 2],
        7 => [3, 3],
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
        7 => ['caller'],
    ];

    /**
     * Список ролей для разработчиков
     * 
     * @var array
     */
    protected $developers = [
        401 => ['developer'],
        424 => ['developer'],
        // 866 => ['developer'],
    ];

    /**
     * Роли для группы прав
     * 
     * @var array
     */
    protected $groupToRole = [
        'caller' => ['caller'],
        'nachColl' => ['callCenterManager'],
        'nachCollSamara' => ['admin'],
    ];

    /**
     * Список сотрудников, которым необходимо обнулить колл-центр
     * 
     * @var array
     */
    protected $sectorClear = [
        1, 132, 401, 402, 424, 497, 666, 813, 866, 890, 920,
    ];

    /**
     * Список сотрудников, которые должны быть заблокированы в БД
     * 
     * @var array
     */
    protected $firedUser = [
        195,    403,    411,    428,    443,    475,    4900,   4901,   4902,
        4903,   4908,   40000,  40001,  40002,  40003,  40004,  40005,  40006,
        40011,  40012,  40013,  40014,  40015,  40016,  40017,  40018,  40019,
        40020,  40021,  40909
    ];

    /**
     * Вывод списка руководителей, разработчиков, сисадминов и тд
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNachUsers()
    {
        return CrmUser::where('state', 'Работает')
            ->whereIn('rights', ['admin', 'nachColl'])
            ->orderBy('pin')
            ->get();
    }

    /**
     * Вывод списка администраторов секторов
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAdmins()
    {
        return CrmUser::where('state', 'Работает')
            ->whereIn('rights', ['nachCollSamara'])
            ->orderBy('pin')
            ->get();
    }

    /**
     * Вывод списка операторов колл-центра
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getcallers()
    {
        return CrmUser::where('state', 'Работает')
            ->whereIn('rights', ['caller'])
            ->orderBy('pin')
            ->get();
    }

    /**
     * Создание строки пользователя
     * 
     * @param CrmUser $user
     * @param string $auth Тип авторизации сотрудника
     * @param bool $fired Флаг уволенного сотрудника
     * @return null|User
     */
    public function createUser($user, $auth = "admin", $fired = false)
    {
        $request = new Request(
            query: [
                'user' => $user->toArray(),
                'auth' => $auth,
                'fired' => $fired,
            ],
            server: [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => env('APP_NAME') . ' ' . env('APP_URL'),
            ]
        );

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

        if ($user->pin == 813)
            $user->username = "abrik";

        if (in_array($user->pin, $this->sectorClear)) {
            $pin = $user->pin;
            $user->position_id = null;
        }

        // ФИО
        $user->fullName = preg_replace('/\s/', ' ', $user->fullName);
        $fio = explode(" ", $user->fullName);

        $callcenter = null;
        $sector = null;

        if (!in_array($user->pin, $this->sectorClear)) {
            $callcenter = $this->oldSectors[$user->{'call-center'}][0] ?? null;
            $sector = $this->oldSectors[$user->{'call-center'}][1] ?? null;
        }

        $create = [
            'pin' => $pin,
            // 'old_pin' => $pin == $user->pin ? null : $user->pin,
            'old_pin' => $user->pin,
            'surname' => $fio[0] ?? null,
            'name' =>  $fio[1] ?? null,
            'patronymic' =>  $fio[2] ?? null,
            'created_at' => $user->reg_date,
            'auth_type' => $auth,
            'password' => "old|" . $user->password,
            'login' => $user->username,
            'callcenter_id' => $callcenter,
            'callcenter_sector_id' => $sector,
            'position_id' => $user->position_id ?? null,
        ];

        if ($fired or in_array($user->pin, $this->firedUser)) {
            $create['deleted_at'] = now();
        }

        if (User::where('pin', $create['pin'])->orWhere('login', $create['login'])->count())
            throw new CreateNewUser('Пользователь уже существует');

        $new = User::create($create);

        // Роли сотрудника
        if ($new and !$fired) {

            $roles = array_merge(
                $this->roles[$user->{'call-center'}] ?? [],
                $this->developers[$new->pin] ?? [],
                $this->groupToRole[$user->rights] ?? [],
            );

            foreach (array_unique($roles) as $role) {
                $new->roles()->attach($role);
            }

            if ($new->position_id) {
                $log = $this->logData($request, $new);

                UsersPositionsStory::create([
                    'log_id' => $log->id,
                    'user_id' => $new->id,
                    'position_new' => $new->position_id,
                    'created_at' => now(),
                ]);
            }
        }

        return $new ?? null;
    }

    /**
     * Создание уволеного оператора
     * 
     * @param string|int $pin
     * @return null|User
     */
    public function createFiredUser($pin)
    {
        if (!$old = CrmUser::where('pin', $pin)->first())
            return null;

        try {
            return $this->createUser($old, "admin", true);
        } catch (\Illuminate\Database\QueryException) {
            return null;
        } catch (CreateNewUser) {
            return null;
        }
    }
}
