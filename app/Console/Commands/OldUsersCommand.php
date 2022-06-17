<?php

namespace App\Console\Commands;

use App\Console\MyOutput;
use App\Http\Controllers\Users\UsersMerge;
use App\Models\ChatRoom;
use App\Models\CrmMka\CrmUser;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;

/**
 * Перенос пользователей из старой ЦРМ в новую
 * Будут созданы учетные записи не уволенных сотрудников
 * Уволенные сотрудники добавятся при переносе заявок
 */
class OldUsersCommand extends Command
{
    use MyOutput;

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
    protected $description = 'Transfer of employees from the old CRM';

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
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->users = new UsersMerge;

        $this->title('Перенос сотрудников');

        $this->question(" Разработчики, руководители и тд... ");
        $this->createUsers($this->users->getNachUsers(), "secret");

        $this->question(" Администраторы секторов... ");
        $this->createUsers($this->users->getAdmins(), "secret", 2);

        $this->question(" Кольщики... ");
        $this->createUsers($this->users->getcallers(), position: 3);

        $this->newLine();
        $this->line("<bg=blue;options=bold>   Сотрудники успешно перенесены   </>");
        $this->newLine();

        return 0;
    }

    /**
     * Обработка группы пользователей
     * 
     * @param \Illuminate\Database\Eloquent\Collection $users
     * @param string $auth Способ авторизации
     * @param null|int $position Должность сотрудника
     *          Начальный список должностей представлен в соответсвующей фабрике
     *          `\Database\Factories\UsersPositionFactory::class`
     * @return null
     */
    public function createUsers($users, $auth = "admin", $position = null)
    {
        foreach ($users as $user) {

            $user->position_id = $position;

            $message = trim("<fg=green;options=bold>{$user->pin}</> <options=bold>{$user->username}</> {$user->fullName}");

            try {
                $created = $this->users->createUser($user, $auth);
                $roles = $created->roles->map(fn ($role) => $role->role)->toArray();
                $this->line("\t" . trim($message . " <fg=yellow>" . implode(" ", $roles)) . "</> ");
            } catch (Exception $e) {
                $this->line("\t<fg=red;options=bold>" . $message . "</> <bg=red;fg=white>" . $e->getMessage() . "</>");
            }
        }

        return null;
    }

    /**
     * Смена идентификаторов служебного чата
     * 
     * @return null
     */
    public function changeChatUsersId()
    {
        $users_id = [];

        ChatRoom::lazy()->each(function ($row) use (&$users_id) {

            $users_id[] = $row->user_id;

            foreach (explode(",", $row->user_to_user) as $user_id) {
                $users_id[] = (int) $user_id;
            }
        });

        CrmUser::whereIn('id', array_values(array_unique($users_id)))
            ->get()
            ->each(function ($row) {

                $user = User::where('pin', $row->pin)->first();
            });

        return null;
    }
}
