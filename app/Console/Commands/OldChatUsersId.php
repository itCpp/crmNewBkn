<?php

namespace App\Console\Commands;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\ChatRoomsUser;
use App\Models\ChatRoomsViewTime;
use App\Models\ChatUsersIdChange;
use App\Models\CrmMka\CrmUser;
use App\Models\User;
use Illuminate\Console\Command;

class OldChatUsersId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'old:chatusersid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Сменяет идентификтары пользователей в чате при переносе ЦРМ';

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
        $this->users_id = [];

        /** Поиск старых идентификаторов */
        ChatRoom::lazy()->each(function ($row) {

            $this->users_id[] = $row->user_id;

            foreach (explode(",", $row->user_to_user) as $user_id) {
                $this->users_id[] = (int) $user_id;
            }
        });

        $users_id = ChatMessage::select('user_id')
            ->distinct()
            ->get()
            ->map(function ($row) {
                return $row->user_id;
            })
            ->toArray();

        $this->users_id = array_values(array_unique([...$this->users_id, ...$users_id]));

        $this->new_to_old = [];

        /** 
         * Соотношение персональных номеров с идентификаторами после последнего переноса
         * При первом переносе в таблицу будут записаны данные соотношений, при последующих
         * переносах, соотношения будут переписаны для переопределения старых идентификаторов
         * 
         * `new_id` - идентификатор сотрудника после переноса
         * `old_id` - идентификатор сотрудника до переноса
         * `crm_old_id` - всегда идентификатор сотрудника старой ЦРМ
         */
        ChatUsersIdChange::whereIn('old_id', $this->users_id)
            ->get()
            ->each(function ($row) {
                $this->new_to_old[$row->old_id] = $row->crm_old_id;
            });

        $users_id = [];

        /** Замена идентификтаоров при наличии ранних переносов */
        foreach ($this->users_id as $user_id) {
            $users_id[] = isset($this->new_to_old[$user_id]) ? $this->new_to_old[$user_id] : $user_id;
        }

        $this->old_to_new = [];
        $this->old_to_pin = [];

        CrmUser::whereIn('id', $users_id)
            ->get()
            ->each(function ($row) {

                $user = User::wherePin($row->pin)->first();

                $this->old_to_new[$row->id] = $user->id ?? null;
                $this->old_to_pin[$row->id] = $user->pin ?? null;
            });

        ChatRoom::lazy()
            ->each(function ($row) {

                $row->user_id = $this->old_to_new[$row->user_id] ?? $row->user_id;

                $user_to_user = explode(",", $row->user_to_user);

                foreach ($user_to_user as &$user_id) {
                    $user_id = $this->old_to_new[$user_id] ?? $user_id;
                }

                sort($user_to_user);

                $row->user_to_user = implode(",", $user_to_user);

                $row->save();
            });

        ChatRoomsUser::lazy()
            ->each(function ($row) {
                $row->user_id = $this->old_to_new[$row->user_id] ?? $row->user_id;
                $row->save();
            });

        ChatRoomsViewTime::lazy()
            ->each(function ($row) {
                $row->user_id = $this->old_to_new[$row->user_id] ?? $row->user_id;
                $row->save();
            });

        ChatMessage::withTrashed()
            ->lazy()
            ->each(function ($row) {
                $row->user_id = $this->old_to_new[$row->user_id] ?? $row->user_id;
                $row->save();
            });

        foreach ($this->old_to_new as $old => $new) {

            $pin = $this->old_to_pin[$old] ?? 0;

            if ($user = CrmUser::wherePin($pin)->first()) {

                $row = ChatUsersIdChange::firstOrNew(['crm_old_id' => $user->id]);
                $row->new_id = $new;
                $row->old_id = $old;
                $row->pin = $user->pin;
                $row->save();
            }
        }

        $this->info("Чат перенастроен для новой ЦРМ");

        return 0;
    }
}
