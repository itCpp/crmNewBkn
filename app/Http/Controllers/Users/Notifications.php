<?php

namespace App\Http\Controllers\Users;

use App\Events\Users\NotificationsEvent;
use App\Models\Fine;
use App\Models\Notification;
use App\Models\User;
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
        $notifications = new static;

        if ($new) {

            $row = self::create([
                'user' => $new,
                'notif_type' => "set_request",
                'notification' => "Вам назначена заявка #{$id}",
                'data' => [
                    'request_id' => $id,
                ],
                'user_by_id' => optional(request()->user())->id,
            ]);

            broadcast(new NotificationsEvent(
                $notifications->serialize($row)->toArray(),
                Users::findUserId($new)
            ));
        }

        if ($old) {

            $row = self::create([
                'user' => $old,
                'notif_type' => "set_request",
                'notification' => "Заявка #{$id} передана другому сотруднику",
                'data' => [
                    'drop_request_id' => $id,
                ],
                'user_by_id' => optional(request()->user())->id,
            ]);

            broadcast(new NotificationsEvent(
                $notifications->serialize($row)->toArray(),
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

        $notifications = new static;

        $notification = self::create([
            'user' => $row->user_pin,
            'notif_type' => "fine",
            'notification' => $message,
            'data' => $row->toArray(),
            'user_by_id' => optional(request()->user())->id,
        ]);

        broadcast(new NotificationsEvent(
            $notifications->serialize($notification)->toArray(),
            Users::findUserId($row->user_pin)
        ));

        return null;
    }

    /**
     * Формирует строку уведомления
     * 
     * @param  \App\Models\Notification $row
     * @return \App\Models\Notification
     */
    public function serialize($row)
    {
        $row->author_data = $this->getAuthor((int) $row->user_by_id);
        $row->author = $row->author_data['fio'] ?? null;

        return $row;
    }

    /**
     * Выводит строку уведомления и устанавливает время прочтения
     * 
     * @param  \Illumiante\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function read(Request $request)
    {
        if (!$row = Notification::find($request->id))
            return response()->json(['message' => "Уведомление не найдено или удалено"], 404);

        if ($row->user != $request->user()->pin)
            return response()->json(['message' => "Доступ к уведомлению ограничен"], 403);

        if (!$row->readed_at) {
            $row->readed_at = now();
            $row->save();
        }

        return response()->json(
            $this->serialize($row)
        );
    }

    /**
     * Определяет автора уведомления
     * 
     * @param  int $id
     * @return array
     */
    public function getAuthor($id)
    {
        if (!empty($this->authors[$id]))
            return $this->authors[$id];

        if (!$row = User::find($id))
            return $this->authors[$id] = [];

        $row = new UserData($row);

        return $this->authors[$id] = [
            'fio' => $row->name_fio,
            'pin' => $row->pin,
        ];
    }

    /**
     * Отмечает все уведомления пользователя как прочитанные
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function readAll(Request $request)
    {
        $readed_at = now();

        Notification::where([
            ['user', $request->user()->pin],
            ['user', '!=', null]
        ])->update([
            'readed_at' => $readed_at,
        ]);

        return response()->json([
            'readed_at' => $readed_at,
        ]);
    }
}
