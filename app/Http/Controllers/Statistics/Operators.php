<?php

namespace App\Http\Controllers\Statistics;

use App\Http\Controllers\Controller;
use App\Models\RequestsRow;
use App\Models\RequestsStoryPin;
use App\Models\RequestsStoryStatus;
use App\Models\Base\CrmComing;
use Illuminate\Http\Request;

class Operators extends Controller
{
    /**
     * Данные по статистике
     * 
     * @var \Illuminate\Support\Collection
     */
    protected $operators;

    /**
     * Сотрудники, задействованные в статистике
     * 
     * @var array
     */
    protected $pins = [];

    /**
     * Создание экземпляра объекта
     * 
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(
        protected Request $request
    ) {
        $this->operators = collect([]);

        $this->now = now();
    }

    /**
     * Handle calls to missing methods on the controller
     * 
     * @param string $method
     * @param array $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        return $this;
    }

    /**
     * Данные по операторам
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getOperators(Request $request)
    {
        return response()->json([
            'operators' => (new static($request))->operators($request),
        ]);
    }

    /**
     * Метод вывода статитсики по операторам
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function operators(Request $request)
    {
        $this->getRequests()
            ->getComings()
            ->getComingsInDay()
            ->getNotRinging()
            ->getDrain()
            ->getRecords()
            ->getRecordsInDay()
            ->getRecordsNextDay()
            ->getRecordsToDay()
            ->getUsersData();

        return $this->operators->flatten()->toArray();
    }

    /**
     * Добавляет в коллекцию значение по ключу
     * 
     * @param string|int $key
     * @param string $name
     * @param mixed $value
     * @return object
     */
    public function append($key, $name, $value)
    {
        if (!$this->operators->has($key)) {
            $this->operators[$key] = (object) [
                'pin' => $key,
                'name' => null,
                'sector' => null,
                'requests' => 0,
                'comings' => 0,
                'comingsInDay' => 0,
                'notRinging' => 0,
                'drain' => 0,
                'records' => 0,
                'recordsInDay' => 0,
                'recordsNextDay' => 0,
                'recordsToDay' => 0,
            ];
        }

        $this->operators[$key]->$name = $value;

        return $this->operators[$key];
    }

    /**
     * Подсчет активных заявок
     * 
     * @return $this
     */
    public function getRequests()
    {
        RequestsStoryPin::selectRaw('count(*) as count, new_pin')
            ->whereDate('created_at', $this->now)
            ->groupBy('new_pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->new_pin, 'requests', $row->count);
            });

        return $this;
    }

    /**
     * Подсчет всех приходов сотрудника за указанный день
     * 
     * @return $this
     */
    public function getComings()
    {
        CrmComing::selectRaw('count(*) as count, collPin as pin')
            ->whereDate('date', $this->now)
            ->whereNotIn('collPin', [
                '', '-', 'цпп', 'улица', 'СП', 'соседка', 'СМИ',
                'сайт', 'Промо', 'павелецкая', 'ОО', 'колл',
                'Иное', 'Вторичка'
            ])
            ->groupBy('pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->pin, 'comings', $row->count);
            });

        return $this;
    }

    /**
     * Подсчет приходов сотрудника, по записям, сделанным день в день
     * 
     * @return $this
     */
    public function getComingsInDay()
    {
        if (!$status = env("STATISTICS_OPERATORS_STATUS_COMING_ID"))
            return $this;

        RequestsStoryStatus::selectRaw('count(*) as count, json_unquote(json_extract(request_data, "$.pin")) as pin')
            ->join('requests_stories', 'requests_stories.id', '=', 'requests_story_statuses.story_id')
            ->whereDate('requests_story_statuses.created_at', $this->now)
            ->whereDate('request_data->created_at', $this->now)
            ->where('requests_story_statuses.status_new', $status)
            ->where('request_data->pin', '!=', null)
            ->groupBy('pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->pin, 'comingsInDay', $row->count);
            });

        return $this;
    }

    /**
     * Подсчет недозвонов у сотрудника
     * 
     * @return $this
     */
    public function getNotRinging()
    {
        if (!$status = env("STATISTICS_OPERATORS_STATUS_NOT_RINGING_ID"))
            return $this;

        RequestsStoryStatus::selectRaw('count(*) as count, json_unquote(json_extract(request_data, "$.pin")) as pin')
            ->join('requests_stories', 'requests_stories.id', '=', 'requests_story_statuses.story_id')
            ->whereDate('requests_story_statuses.created_at', $this->now)
            ->whereDate('request_data->created_at', $this->now)
            ->where('requests_story_statuses.status_new', $status)
            ->where('request_data->pin', '!=', null)
            ->groupBy('pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->pin, 'notRinging', $row->count);
            });

        return $this;
    }
}
