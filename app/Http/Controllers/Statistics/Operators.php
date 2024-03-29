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
    use OperatorsColumns, OperatorsOld;

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
        $operators = new static($request);

        return response()->json([
            'operators' => $operators->operators($request),
            'columns' => $operators->columns,
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
        if (env('NEW_CRM_OFF', true))
            return $this->operatorsOld($request);

        $this->getRequests()
            ->getComings()
            ->getRecords()
            ->getComingsInDay()
            ->getRecordsDay()
            ->getRecordsInDay()
            ->getRecordsNextDay()
            ->getRecordsToDay()
            ->getNotRinging()
            ->getDrain()
            ->getTotals();

        return $this->operators->flatten()->sortBy([
            ['efficiency', 'desc'],
        ])->toArray();
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
            ];

            foreach ($this->columns as $column) {
                $this->operators[$key]->{$column['name']} = 0;
            }
        }

        $this->operators[$key]->$name = $value;

        return $this->operators[$key];
    }

    /**
     * Получает идентификаторы статусов
     * 
     * @param string $key
     * @return null|array
     */
    public function getStatus($key = null)
    {
        if (!$status = env($key))
            return null;

        return explode(",", $status);
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
     * Подсчет записей сотрудника на сегодня
     * 
     * @return $this
     */
    public function getRecords()
    {
        if (!$status = $this->getStatus("STATISTICS_OPERATORS_STATUS_RECORD_ID"))
            return $this;

        RequestsRow::selectRaw('count(*) as count, pin')
            ->whereDate('event_at', $this->now)
            ->whereIn('status_id', $status)
            ->where('pin', '!=', null)
            ->groupBy('pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->pin, 'records', $row->count);
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
        if (!$status = $this->getStatus("STATISTICS_OPERATORS_STATUS_COMING_ID"))
            return $this;

        RequestsStoryStatus::selectRaw('count(*) as count, json_unquote(json_extract(request_data, "$.pin")) as pin')
            ->join('requests_stories', 'requests_stories.id', '=', 'requests_story_statuses.story_id')
            ->whereDate('requests_story_statuses.created_at', $this->now)
            ->whereDate('request_data->created_at', $this->now)
            ->whereIn('requests_story_statuses.status_new', $status)
            ->whereIn('request_data->pin', $this->operators->keys())
            // ->where('request_data->pin', '!=', null)
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
        if (!$status = $this->getStatus("STATISTICS_OPERATORS_STATUS_NOT_RINGING_ID"))
            return $this;

        RequestsStoryStatus::selectRaw('count(*) as count, json_unquote(json_extract(request_data, "$.pin")) as pin')
            ->join('requests_stories', 'requests_stories.id', '=', 'requests_story_statuses.story_id')
            ->whereDate('requests_story_statuses.created_at', $this->now)
            ->whereDate('request_data->created_at', $this->now)
            ->whereIn('requests_story_statuses.status_new', $status)
            ->whereIn('request_data->pin', $this->operators->keys())
            // ->where('request_data->pin', '!=', null)
            ->groupBy('pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->pin, 'notRinging', $row->count);
            });

        return $this;
    }

    /**
     * Подсчет сливов сотрудника
     * 
     * @return $this
     */
    public function getDrain()
    {
        if (!$status = $this->getStatus("STATISTICS_OPERATORS_STATUS_DRAIN_ID"))
            return $this;

        RequestsStoryStatus::selectRaw('count(*) as count, json_unquote(json_extract(request_data, "$.pin")) as pin')
            ->join('requests_stories', 'requests_stories.id', '=', 'requests_story_statuses.story_id')
            ->whereDate('requests_story_statuses.created_at', $this->now)
            ->whereDate('request_data->created_at', $this->now)
            ->whereIn('requests_story_statuses.status_new', $status)
            ->whereIn('request_data->pin', $this->operators->keys())
            // ->where('request_data->pin', '!=', null)
            ->groupBy('pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->pin, 'drain', $row->count);
            });

        return $this;
    }

    /**
     * Подсчет записей сотрудника, сделанных за сегодня
     * 
     * @return $this
     */
    public function getRecordsDay()
    {
        if (!$status = $this->getStatus("STATISTICS_OPERATORS_STATUS_RECORD_ID"))
            return $this;

        RequestsRow::selectRaw('count(*) as count, pin')
            ->whereDate('created_at', $this->now)
            ->whereIn('status_id', $status)
            ->whereIn('pin', $this->operators->keys())
            ->groupBy('pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->pin, 'recordsDay', $row->count);
            });

        return $this;
    }

    /**
     * Подсчет записей день в день
     * 
     * @return $this
     */
    public function getRecordsInDay()
    {
        if (!$status = $this->getStatus("STATISTICS_OPERATORS_STATUS_RECORD_ID"))
            return $this;

        RequestsRow::selectRaw('count(*) as count, pin')
            ->whereDate('created_at', $this->now)
            ->whereDate('event_at', $this->now)
            ->whereIn('status_id', $status)
            ->whereIn('pin', $this->operators->keys())
            ->groupBy('pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->pin, 'recordsInDay', $row->count);
            });

        return $this;
    }

    /**
     * Подсчет записей на следующий день из сегодняшних заявок
     * 
     * @return $this
     */
    public function getRecordsNextDay()
    {
        if (!$status = $this->getStatus("STATISTICS_OPERATORS_STATUS_RECORD_ID"))
            return $this;

        RequestsRow::selectRaw('count(*) as count, pin')
            ->whereDate('created_at', $this->now)
            ->whereDate('event_at', $this->now->copy()->addDay(1))
            ->whereIn('status_id', $status)
            ->whereIn('pin', $this->operators->keys())
            ->groupBy('pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->pin, 'recordsNextDay', $row->count);
            });

        return $this;
    }

    /**
     * Подсчет записей на следующий день
     * 
     * @return $this
     */
    public function getRecordsToDay()
    {
        if (!$status = $this->getStatus("STATISTICS_OPERATORS_STATUS_RECORD_ID"))
            return $this;

        RequestsRow::selectRaw('count(*) as count, pin')
            ->whereDate('event_at', $this->now->copy()->addDay(1))
            ->whereIn('status_id', $status)
            ->whereIn('pin', $this->operators->keys())
            ->groupBy('pin')
            ->get()
            ->each(function ($row) {
                $this->append($row->pin, 'recordsToDay', $row->count);
            });

        return $this;
    }

    /**
     * Подведение итогов по найденным данным
     * 
     * @return $this
     */
    public function getTotals()
    {
        $this->operators->map(function ($row) {

            if ($user = $this->getUserData($row->pin)) {
                $row->userId = $user->id;
                $row->name = $user->name_full;
                $row->sector = $user->getSectorName();
            }

            if ($row->requests ?? 0)
                $row->efficiency = round(($row->comings / $row->requests) * 100, 1);

            return $row;
        });

        return $this;
    }

    /**
     * Поиск данных сотрудника
     * 
     * @param string|int $pin
     * @return \App\Http\Controllers\Users\UserData|null
     */
    public function getUserData($pin)
    {
        if (!empty($this->users_data[$pin]))
            return $this->users_data[$pin];

        return $this->users_data[$pin] = \App\Http\Controllers\Users\Users::findUserPin($pin);
    }
}
