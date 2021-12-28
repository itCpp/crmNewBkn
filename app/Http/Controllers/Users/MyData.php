<?php

namespace App\Http\Controllers\Users;

use App\Exceptions\ExceptionsJsonResponse;
use App\Http\Controllers\Ratings\CallCenters;
use App\Http\Controllers\Statistics\Charts;
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
        if ($request->user()->id != $request->userId)
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
            'user' => $request->user(),
            'rating' => (new CallCenters($request))->getMyRow($request->user()->pin),
            'charts' => (new Charts($request))->getCharts($request),
        ];
    }
}
