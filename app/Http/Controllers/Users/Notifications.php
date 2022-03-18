<?php

namespace App\Http\Controllers\Users;

use App\Events\Users\NotificationsEvent;
use App\Models\Notification;
use Illuminate\Http\Request;

class Notifications
{
    /**
     * Создание уведомления
     * 
     * @param array $data
     * @return \App\Models\Notification
     */
    public static function create($data)
    {
        return Notification::create($data);
    }

    /**
     * Отправка уведомлений при смене оператора в заявке
     * 
     * @param int $id
     * @param int|null $new
     * @param int|null $old
     * @return null
     */
    public static function changeRequestPin($id, $new, $old)
    {
        if ($new) {
            broadcast(new NotificationsEvent(
                self::create([
                    'user' => $new,
                    'notif_type' => "set_request",
                    'notification' => "Вам назначена заявка #{$id}",
                    'data' => [
                        'request_id' => $id,
                    ],
                    'user_by_id' => optional(request()->user())->id,
                ]),
                Users::findUserId($new)
            ));
        }

        if ($old) {
            broadcast(new NotificationsEvent(
                self::create([
                    'user' => $old,
                    'notif_type' => "set_request",
                    'notification' => "Заявка #{$id} передана другому сотруднику",
                    'user_by_id' => optional(request()->user())->id,
                ]),
                Users::findUserId($old)
            ));
        }

        return null;
    }
}
