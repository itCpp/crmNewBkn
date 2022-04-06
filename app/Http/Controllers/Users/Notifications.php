<?php

namespace App\Http\Controllers\Users;

use App\Events\Users\NotificationsEvent;
use App\Models\Fine;
use App\Models\Notification;
use Illuminate\Http\Request;

class Notifications
{
    /**
     * Создание уведомления
     * 
     * @param  array $data
     * @return \App\Models\Notification
     */
    public static function create($data)
    {
        return Notification::create($data);
    }

    /**
     * Отправка уведомлений при смене оператора в заявке
     * 
     * @param  int $id Идентификатор заявки
     * @param  int|null $new Персональный номер нового сотрудника
     * @param  int|null $old Персональный номер старого сотрудника
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
                    'data' => [
                        'drop_request_id' => $id,
                    ],
                    'user_by_id' => optional(request()->user())->id,
                ]),
                Users::findUserId($old)
            ));
        }

        return null;
    }

    /**
     * Штрафные уведомления
     * 
     * @param  \App\Models\Fine $row
     * @param  null|string $message
     * @return null
     */
    public static function createFineNotification(Fine $row, $message = null)
    {
        if (!$message) {

            $message = "Вам назначен штраф в размере {$row->fine} руб";

            if ($row->comment)
                $message .= ", по причине {$row->comment}";
        }

        broadcast(new NotificationsEvent(
            self::create([
                'user' => $row->user_pin,
                'notif_type' => "fine",
                'notification' => $message,
                'data' => $row->toArray(),
                'user_by_id' => optional(request()->user())->id,
            ]),
            Users::findUserId($row->user_pin)
        ));

        return null;
    }
}
