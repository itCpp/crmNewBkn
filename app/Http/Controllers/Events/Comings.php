<?php

namespace App\Http\Controllers\Events;

use App\Events\Requests\UpdateRequestEvent;
use App\Events\Users\NotificationsEvent;
use App\Exceptions\ExceptionsJsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Requests\Requests;
use App\Http\Controllers\Users\UserData;
use App\Http\Controllers\Users\Users;
use App\Models\Notification;
use App\Models\RequestsRow;
use App\Models\RequestsStory;
use App\Models\RequestsStoryStatus;
use App\Models\User;
use Illuminate\Http\Request;

class Comings extends Controller
{
    /**
     * Обработка события об отметке прихода клиента
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function incomingEvent(Request $request)
    {
        $coming = new static;

        return response()->json($coming->newComing($request));
    }

    /**
     * Отметка о приходе клиента
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function newComing(Request $request)
    {
        if (!$row = RequestsRow::find($request->id))
            throw new ExceptionsJsonResponse("Заявка с указанным идентификтаором не найден", 400);

        $statuses = $this->envExplode('STATISTICS_OPERATORS_STATUS_COMING_ID');

        if (!count($statuses))
            throw new ExceptionsJsonResponse("Идентификатор статуса не определен в настройках", 400);

        if (in_array($row->status_id, $statuses))
            throw new ExceptionsJsonResponse("Заявка уже отмечена приходом", 400);

        $status_old = $row->status_id;

        $row->status_id = $statuses[0];
        $row->event_at = now();
        $row->uplift = 0;
        $row->updated_at = null;

        $row->save();

        /** Логирование изменений заявки */
        $story = RequestsStory::write($request, $row);

        /** Логирование изменения статуса */
        RequestsStoryStatus::create([
            'story_id' => $story->id,
            'request_id' => $row->id,
            'status_old' => $status_old,
            'status_new' => $row->status_id,
            'created_pin' => optional($request->user())->pin,
            'created_at' => now(),
        ]);

        /** Уведомление оператору */
        if ($row->pin) {

            $notification = Notification::create([
                'user' => $row->pin,
                'notif_type' => "coming",
                'notification' => "Новый приход по заявке #{$row->id}",
                'data' => [
                    'coming_request' => $row->id,
                ],
            ]);

            broadcast(new NotificationsEvent($notification, Users::findUserId($row->pin)));
        }

        request()->setUserResolver(function () {
            return new UserData(new User);
        });

        /** Уведомление об обновлении заявки */
        broadcast(new UpdateRequestEvent(Requests::getRequestRow($row)));

        return [
            'message' => "Заявка отмечена приходом",
            'id' => $request->id,
        ];
    }
}
