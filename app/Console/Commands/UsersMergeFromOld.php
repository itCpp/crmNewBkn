<?php

namespace App\Console\Commands;

use App\Http\Controllers\Users\UsersMerge;
use App\Models\CrmMka\CrmUser;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Перенос пользователей из старой ЦРМ в новую
 * Будут созданы учетные записи не уволенных сотрудников
 * Уволенные сотрудники добавятся при переносе заявок
 */
class UsersMergeFromOld extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'old:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Миграция пользователей из старой ЦРМ';

    /**
     * Экземпляр объекта обработки
     * 
     * @var UsersMerge
     */
    protected $users;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->users = new UsersMerge;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->question(" Разработчики, руководители и тд... ");
        $this->createUsers($this->users->getNachUsers(), "secret");

        $this->question(" Администраторы секторов... ");
        $this->createUsers($this->users->getAdmins(), "secret");

        $this->question(" Кольщики... ");
        $this->createUsers($this->users->getcallers());

        return 0;
    }

    /**
     * Обработка группы пользователей
     * 
     * @param \Illuminate\Database\Eloquent\Collection $users
     * @param string $auth Способ авторизации
     * @return null
     */
    public function createUsers($users, $auth = "admin")
    {
        foreach ($users as $user) {

            $message = trim("{$user->pin} {$user->username} {$user->fullName}");

            try {
                $created = $this->users->createUser($user, $auth);
                $roles = $created->roles->map(fn ($role) => $role->role)->toArray();
                $this->info(" " . trim($message . " ". implode(" ", $roles)) . " ");
            } catch (\Illuminate\Database\QueryException $e) {
                $this->error(" " . $message . " ");
            }
        }

        return null;
    }
}
