<?php

namespace App\Http\Controllers\Users;

use App\Exceptions\ExceptionsJsonResponse;
use App\Http\Controllers\Ratings\CallCenters;
use App\Http\Controllers\Sip\SipMain;
use App\Http\Controllers\Statistics\Charts;
use App\Http\Controllers\Requests\Requests;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class UserMainData
{
    /**
     * Обработка запроса на вывод данных
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $userId = $request->userId ?: $request->user()->id;

        if ($request->user()->id != $userId and !$request->user()->can("user_data_any_show"))
            throw new ExceptionsJsonResponse("Доступ ограничен", 403);

        if ($request->user()->id != $userId) {
            
            if (!$user = User::find($userId))
                throw new ExceptionsJsonResponse("Сотрудник не найден", 403);

            $request->setUserResolver(function () use ($user) {
                return new UserData($user);
            });
        }

        return response()->json(
            $this->getMyData($request),
        );
    }

    /**
     * Сбор данных для статистики
     * 
     * @param \Illuminate\Http\Request
     * @return array
     * 
     * @todo Вывести новые сообщения из внутреннего чата
     */
    public function getMyData(Request $request)
    {
        $request->toChats = true; # Вывод данных для графиков рейтинга

        return [
            'alerts' => $this->getMyDataAlerts($request),
            'rating' => (new CallCenters($request))->getMyRow($request->user()->pin),
            'charts' => (new Charts($request))->getCharts($request),
            'user' => $request->user(),
            'worktime' => Worktime::getTapeTimes($request),
            'calls' => (new SipMain)->getTapeTimes($request),
        ];
    }

    /**
     * Выводит массив с уведомлениями
     * 
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function getMyDataAlerts(Request $request)
    {
        return [
            'requests' => Requests::getNewRequests($request->user()->pin),
            'notifications' => $this->getNotifications($request),
            // 'chat' => null,
        ];
    }

    /**
     * Список уведомлений
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getNotifications(Request $request)
    {
        $data = Notification::where('user', $request->user()->pin)
            ->orderBy('id', "DESC");

        $notifications = new Notifications;
        $rows = $data->limit(50)->get()
            ->map(function ($row) use ($notifications) {
                return $notifications->serialize($row);
            });

        return [
            'count' => $data->count(),
            'rows' => $rows,
            'recent' => $data->where('readed_at', null)->count(),
        ];
    }

    /**
     * Вывод данных для временных шкал
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTapeTimes(Request $request)
    {
        return response()->json([
            'worktime' => Worktime::getTapeTimes($request),
        ]);
    }
}
