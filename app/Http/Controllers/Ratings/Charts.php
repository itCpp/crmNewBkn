<?php

namespace App\Http\Controllers\Ratings;

use App\Http\Controllers\Controller;
use App\Models\RatingStory;
use Illuminate\Http\Request;

class Charts extends Controller
{
    /**
     * Количество дней, за которые выводится информация
     * 
     * @var int
     */
    const DAYS = 30;

    /**
     * Перевод колонок
     * 
     * @var array
     */
    protected $translate = [
        'requests' => "Заявки",
        'comings' => "Приходы",
        'records' => "Записи",
        'drain' => "Сливы",
    ];

    /**
     * @param \Illumiante\Http\Request $request
     * @return array
     */
    public function __invoke(Request $request)
    {
        $data = [];

        RatingStory::where('to_day', '>=', now()->subDays(self::DAYS))
            ->where('to_day', '!=', null)
            ->get()
            ->each(function ($row) use (&$data) {

                if (!isset($data[$row->to_day])) {
                    $data[$row->to_day] = [
                        'requests' => 0,
                        'comings' => 0,
                        'records' => 0,
                        'drain' => 0,
                    ];
                }

                $data[$row->to_day]['requests'] += $row->rating_data->requests ?? 0;
                $data[$row->to_day]['comings'] += $row->rating_data->comings ?? 0;
                $data[$row->to_day]['records'] += $row->rating_data->records ?? 0;
                $data[$row->to_day]['drain'] += $row->rating_data->drain ?? 0;
            });

        foreach ($data as $date => $row) {

            foreach ($row as $type => $value) {

                if ((int) $value == 0)
                    continue;

                $response[] = [
                    'date' => $date,
                    'type' => $this->translate[$type] ?? $type,
                    'value' => (int) $value,
                ];
            }
        }

        return collect($response ?? [])
            ->sortBy('date')
            ->values()
            ->all();
    }
}
