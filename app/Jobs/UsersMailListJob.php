<?php

namespace App\Jobs;

use App\Events\AppUserEvent;
use App\Events\Users\MailListAdminEvent;
use App\Events\Users\MailListEvent;
use App\Events\Users\NotificationsEvent;
use App\Models\Notification;
use App\Models\User;
use App\Models\UsersMailList;
use App\Models\UsersSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UsersMailListJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  \App\Models\UsersMailList $row
     * @return void
     */
    public function __construct(
        protected UsersMailList $row
    ) {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->toast = [
            'title' => $this->row->title ?: "Уведомление",
            'type' => $this->row->type ?: "info",
            'description' => $this->row->message,
            'time' => 0,
        ];

        if ($this->row->icon)
            $this->toast['icon'] = $this->row->icon;

        if ($this->row->to_push and !$this->row->to_notice) {
            $this->sendPush($this->row);
        } else if ($this->row->to_notice or $this->row->to_online) {
            $this->sendNotice((bool) $this->row->to_push);
        }

        $this->row->done_at = now();
        $this->row->save();

        broadcast(new MailListAdminEvent($this->row));
    }

    /**
     * Отправка быстрых уведомлений
     * 
     * @return null
     */
    public function sendPush()
    {
        broadcast(new MailListEvent(toast: $this->toast));

        $this->row->response = array_merge(
            $this->row->response,
            ['to_push' => now()->format("Y-m-d H:i:s")]
        );
    }

    /**
     * Отправка обычныйх уведомлений
     * 
     * @param  boolean $push
     * @return null
     */
    public function sendNotice($push = false)
    {
        $to_notice = [
            'users_id' => [],
            'start' => now()->format("Y-m-d H:i:s"),
        ];

        if ($push) {
            $to_notice['users_id_push'] = [];
        }

        $this->user_by = User::where('pin', $this->row->author_pin)->first();
        $this->user_by_id = optional($this->user_by)->id;

        $online = UsersSession::select('user_pin')
            ->where('created_at', '>=', now()->startOfDay())
            ->distinct()
            ->get()
            ->map(function ($row) {
                return $row->user_pin;
            })
            ->toArray();

        $to_notice['online'] = $online;

        if ($this->row->to_online and !count($online)) {

            $this->row->response = array_merge(
                $this->row->response,
                ['to_notice' => $to_notice]
            );

            return null;
        }

        User::where('deleted_at', null)
            ->when((count($online) > 0 and (bool) $this->row->to_online), function ($query) use (&$online) {
                $query->whereIn('pin', $online);
            })
            ->lazy()
            ->each(function ($row) use (&$to_notice, $push, &$online) {

                $notification = Notification::create([
                    'user' => $row->pin,
                    'notif_type' => $this->row->type,
                    'mail_list_id' => $this->row->id,
                    'notification' => $this->row->message,
                    'data' => $this->toast,
                    'user_by_id' => $this->row->anonim ? null : $this->user_by_id,
                ]);

                $to_notice['users_id'][$row->id] = $notification->id;

                if ($push) {
                    $to_notice['users_id_push'][] = $row->id;
                    broadcast(new AppUserEvent(id: $row->id, alert: $this->toast));

                    if (in_array($row->pin, $online ?? []))
                        broadcast(new NotificationsEvent($notification, $row->id, true));
                }
            });

        $to_notice['stop'] = now()->format("Y-m-d H:i:s");

        $this->row->response = array_merge(
            $this->row->response,
            ['to_notice' => $to_notice]
        );

        return null;
    }
}
