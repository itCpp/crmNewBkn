<?php

namespace App\Http\Controllers\Users;

use App\Exceptions\ExceptionsJsonResponse;
use App\Http\Controllers\Ratings\CallCenters;
use App\Http\Controllers\Statistics\Charts;
use App\Http\Controllers\Requests\Requests;
use App\Models\Notification;
use Illuminate\Http\Request;

class MyData
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

        if ($request->user()->id != $userId)
            throw new ExceptionsJsonResponse("Доступ ограничен", 403);

        return response()->json(
            $this->getMyData($request),
        );
    }

    /**
     * Сбор данных для статистики
     * 
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function getMyData(Request $request)
    {
        return [
            'alerts' => [
                'requests' => Requests::getNewRequests($request->user()->pin),
                'notifications' => $this->getNotifications($request),
            ],
            'rating' => (new CallCenters($request))->getMyRow($request->user()->pin),
            'charts' => (new Charts($request))->getCharts($request),
            'user' => $request->user(),
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
        $notif = Notification::where('user_id', $request->user()->id)
            ->orderBy('id', "DESC");

        return [
            'count' => $notif->count(),
            'rows' => $notif->limit(50)->get(),
            'recent' => Notification::where('readed_at', null)->count(),
        ];
    }
}
