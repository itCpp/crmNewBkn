<?php

namespace App\Http\Controllers\Requests;

use App\Exceptions\CreateRequestsSqlQuery;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Dates;
use App\Models\RequestsRow;
use Illuminate\Http\Request;

/**
 * Объект формирует запрос для вывода заявок
 * с применением фильтров, разрешений пользователя и
 * поисковых запросов
 */
class RequestsQuery extends Controller
{
    use RequestsQuerySearch;

    /**
     * Колонки, разрешенные к применению фильтрации по дате
     * 
     * @var array
     */
    protected $dates_columns = [
        'created_at',
        'updated_at',
        'event_at',
        'uplift_at',
    ];

    /**
     * Определение типов переменных для конструктора запросов
     * 
     * @var array
     */
    protected $typeWhereValue = [
        'whereIn' => "list",
        'whereNotIn' => "list",
        'whereBetween' => "between",
        'whereNotBetween' => "between",
    ];

    /**
     * Методы конструктора, принимающице в качестве первого аргумента
     * массив с массивами аргументов и занчений
     * 
     * @var array
     */
    protected $whereArgumentsArray = [
        'where',
        'orWhere'
    ];

    /**
     * Методы конструктора, принимающице только один аргумент
     * 
     * @var array
     */
    protected $whereOneArguments = [
        'whereNull',
        'whereNotNull',
        'orWhereNull',
        'orWhereNotNull'
    ];

    /**
     * Фильтр данных
     * 
     * @var object
     */
    protected $filter;

    /**
     * Модель заявки
     * 
     * @var \App\Models\RequestsRow
     */
    protected $model;

    /**
     * Модель вкладки
     * 
     * @var \App\Models\Tab
     */
    protected $tab;

    /**
     * Экземпляр данных пользователя
     * 
     * @var \App\Http\Controllers\Users\UserData
     */
    protected $user;

    /**
     * Обработчик дат
     * 
     * @var \App\Http\Controllers\Dates
     */
    protected $dates;

    /**
     * Данные поискового запроса
     * 
     * @var object|null
     */
    protected $search = null;

    /**
     * Создание объекта
     * 
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->model = new RequestsRow;

        $this->user = $request->user();

        $this->filter = (object) [];

        $this->dates = new Dates($request->start, $request->stop);

        $this->filter->start = $this->dates->startDateTime;
        $this->filter->stop = $this->dates->stopDateTime;

        $this->tab = $request->tab;

        if ($request->search)
            $this->search = new RequestsQuerySearchParams($request->search);
    }

    /**
     * Вывод сформированного запроса
     * 
     * @return string
     */
    public function toSql()
    {
        return $this->setWhere()->setOrderBy()->model->toSql();
    }

    /**
     * Формирование запроса и применение фильтров
     * 
     * @param array $params
     * @return \App\Models\RequestsRow
     */
    public function where(...$params)
    {
        // Поисковой запрос
        if ($this->search) {

            if (!$this->search->getQueryKeysCount())
                throw new CreateRequestsSqlQuery("Введите поисковой запрос");

            return $this->setSearchQuery();
        }

        $this->setWhere()
            ->setDatesFilter()
            ->setOptionsTabFilter()
            ->setUserPermitsFilter()
            ->setWhereFromParams($params)
            ->setOrderBy();

        return $this->model;
    }

    /**
     * Вывод данных с разбивкой на страницы
     * 
     * @param int $limit Количество строк за один запрос
     * @return \App\Models\RequestsRow
     */
    public function paginate($limit = 25)
    {
        return $this->where()->paginate($limit ?? 25);
    }

    /**
     * Выводит количество найденных строк
     * 
     * @return int
     */
    public function count()
    {
        return $this->where()->count();
    }

    /**
     * Применение фильтра по правам пользователя
     * 
     * @return $this
     */
    public function setUserPermitsFilter()
    {
        if (!$this->user)
            return $this;

        // $this->model = $this->model->where('filter', 1);

        return $this;
    }

    /**
     * Применение условий из настроек вкладки
     * 
     * @return $this
     */
    public function setWhere()
    {
        // Настройки условий во вкладке
        $params = $this->tab->where_settings ?? [];

        if (!count($params))
            return $this;

        // Условия из настроек применяются в отдельных скобках
        $this->model = $this->model->where(function ($query) use ($params) {

            foreach ($params as $wheres) {

                $method = $wheres['where'] ?? null; // Метод выражения

                // Применение простых запросов
                if ($method and $method != "whereFunction") {

                    // Список атрибутов
                    $attrts = $wheres['attr'] ?? [];

                    // Метод может принимать первым аргументом массив из множества условий
                    $toArray = in_array($method, $this->whereArgumentsArray);

                    // Массив условий
                    $where = [];

                    // Обход аргументов
                    foreach ($attrts as $attr) {

                        $keys = array_keys($attr);

                        $column = in_array('column', $keys) ? $attr['column'] : null; // Наименование колонки
                        $operator = in_array('operator', $keys) ? $attr['operator'] : null; // Оператор условия

                        $value = in_array('value', $keys) ? $attr['value'] : null; // Строчное значение
                        $between = in_array('between', $keys) ? $attr['between'] : null; // Период значений от и до
                        $list = in_array('list', $keys) ? $attr['list'] : null; // Список значений

                        // Необходимо наименование колонки
                        if ($column) {

                            $row = null;

                            // Разделение на типы аргументов
                            if (in_array($method, $this->whereOneArguments))
                                $row = [$column];
                            elseif ($value !== null and $operator)
                                $row = [$column, $operator, $value];
                            elseif ($value !== null)
                                $row = [$column, $value];
                            elseif (is_array($between) and count($between) == 2)
                                $row = [$column, $between];
                            elseif (is_array($list))
                                $row = [$column, $list];

                            if ($row)
                                $where[] = $row;
                        }

                        // Остановка цикла для методов, принимающих определенное количество аргументов
                        if (!$toArray)
                            break;
                    }

                    // Проверка наличия аргументов для применения конструктора запроса
                    $arguments = count($where);

                    if ($arguments > 0) {
                        $query = $arguments == 1
                            ? $query->$method(...$where[0] ?? [])
                            : $query = $this->model->$method($where);
                    }
                }
            }
        });

        return $this;
    }

    /**
     * Применение фильтра по дате
     * 
     * @return $this
     */
    public function setDatesFilter()
    {
        // Фильтр по дате не применять
        if ($this->tab->date_view ?? null)
            return $this;

        // Применение стандартного фильтра по дате
        if (!($this->tab->date_types ?? null))
            return $this->setDefaultDatesFilter();

        $set = false; # Флаг примененного фильтра

        if (is_array($this->tab->date_types)) {

            $this->model = $this->model->where(function ($query) use (&$set) {

                foreach ($this->tab->date_types as $type => $bool) {

                    if ($bool and in_array($type, $this->dates_columns)) {

                        $set = true; # Флаг примененного фильтра

                        $query->orWhereBetween($type, [
                            $this->filter->start,
                            $this->filter->stop
                        ]);
                    }
                }
            });
        }

        // В случае, если фильтр не был применен
        if (!$set)
            return $this->setDefaultDatesFilter();

        return $this;
    }

    /**
     * Применение стандартного фильтра по дате
     * 
     * @return $this
     */
    public function setDefaultDatesFilter()
    {
        $this->model = $this->model->whereBetween('created_at', [
            $this->filter->start,
            $this->filter->stop
        ]);

        return $this;
    }

    /**
     * Применение фильтра настрок вкладки
     * 
     * @return $this
     */
    public function setOptionsTabFilter()
    {
        if (!$this->tab and !$this->user)
            throw new CreateRequestsSqlQuery("Ошибка формирования запроса на вывод данных");

        // Применение фильтра вывода статусов
        if ($this->tab->statuses)
            $this->model = $this->model->whereIn('status_id', $this->tab->statuses);

        // Применение фильтра игнорирования статусов
        if ($this->tab->statuses_not)
            $this->model = $this->model->whereNotIn('status_id', $this->tab->statuses_not);

        // Фильтр по публичным правам вкладки
        if ($this->tab->request_all_permit == 1)
            return $this->setOptionsTabFilterForUserPermits();

        // Вывод только своих заявок
        if ($this->tab->request_all == "my" or $this->tab->request_all === null)
            return $this->setOptionsTabFilterMyRows();

        // Вывод заявок своего сектора
        if ($this->tab->request_all == "sector")
            return $this->setOptionsTabFilterMySector();

        // Вывод заявок своего сектора
        if ($this->tab->request_all == "callcenter")
            return $this->setOptionsTabFilterMyCallcenter();

        return $this;
    }

    /**
     * Применение фильтра с учетом разрешений сотрудника
     * 
     * @return $this
     */
    public function setOptionsTabFilterForUserPermits()
    {
        // $my = $this->tab->request_all == "my" or $this->tab->request_all === null;
        $mySector = $this->tab->request_all == "sector";
        $myCallcenter = $this->tab->request_all == "callcenter";
        $myAll = $this->tab->request_all == "all";

        // Вывод всех заявок
        if ($myAll or $this->user->can('requests_all_callcenters'))
            return $this;

        // Ввывод заявок всего коллцентра
        if ($myCallcenter or $this->user->can('requests_all_sectors')) {

            $this->model = $this->model->where(function ($query) {
                $query->when(count($this->user->getAllSectors()) > 0, function ($query) {
                    $query->where(function ($query) {
                        $query->whereIn('callcenter_sector', $this->user->getAllSectors())
                            ->whereNotNull('callcenter_sector');
                    })->orWhere('pin', $this->user->pin);
                });
            });

            return $this;
        }

        // Вывод всех заявок сектора
        if ($mySector or $this->user->can('requests_all_my_sector')) {
            $this->model = $this->model->where(function ($query) {
                $query->when($this->user->callcenter_sector_id !== null, function ($query) {
                    $query->where([
                        ['callcenter_sector', $this->user->callcenter_sector_id],
                        ['callcenter_sector', '!=', null],
                    ])->orWhere('pin', $this->user->pin);
                });
            });

            return $this;
        }

        $this->model = $this->model->where('pin', $this->user->pin);

        return $this;
    }

    /**
     * Вывод только своих заявок
     * 
     * @return $this
     */
    public function setOptionsTabFilterMyRows()
    {
        $this->model = $this->model->where('pin', $this->user->pin);

        return $this;
    }

    /**
     * Вывод заявок своего сектора
     * 
     * @return $this
     */
    public function setOptionsTabFilterMySector()
    {
        $this->model = $this->model->where(function ($query) {
            $query->when($this->user->callcenter_sector_id !== null, function ($query) {
                $query->where([
                    ['callcenter_sector', $this->user->callcenter_sector_id],
                    ['callcenter_sector', '!=', null],
                ])->orWhere('pin', $this->user->pin);
            });
        });

        return $this;
    }

    /**
     * Вывод заявок своего сектора
     * 
     * @return $this
     */
    public function setOptionsTabFilterMyCallcenter()
    {
        $this->model = $this->model->where(function ($query) {
            $query->when(count($this->user->getAllSectors()) > 0, function ($query) {
                $query->where(function ($query) {
                    $query->whereIn('callcenter_sector', $this->user->getAllSectors())
                        ->whereNotNull('callcenter_sector');
                })->orWhere('pin', $this->user->pin);
            });
        });

        return $this;
    }

    /**
     * Применение условий сортировки
     * 
     * @return $this
     */
    public function setOrderBy()
    {
        // Настройки сортировки во вкладке
        $params = $this->tab->order_by_settings ?? [];

        if (!count($params))
            return $this;

        foreach ($params as $orders) {

            $method = $orders['where'] ?? null; // Методы выражения

            if ($method == "orderBy") {

                $column = $orders['column'] ?? null; // Колонка для сортировки
                $by = $orders['by'] ?? null; // Метод сортировки

                $order_by = []; // Атрибуты сортировки

                if ($column)
                    $order_by[] = $column;

                // По умолчнию сортировка по возрастанию
                if ($column && in_array($by, ["ASC", "DESC"]))
                    $order_by[] = $by;

                // Применение сортировки
                if (count($order_by))
                    $this->model = $this->model->orderBy(...$order_by);
            }
        }

        return $this;
    }

    /**
     * Применение условий при вызове метода формирования запроса
     * 
     * @param array $params
     * @return $this
     */
    public function setWhereFromParams($params)
    {
        if ($params)
            $this->model = $this->model->where(...$params);

        return $this;
    }
}
