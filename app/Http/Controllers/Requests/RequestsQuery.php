<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Exceptions\CreateRequestsSqlQuery;
use App\Models\RequestsRow;
use Illuminate\Http\Request;

/**
 * Объект формирует запрос для вывода заявок
 * с применением фильтров, разрешений пользователя и
 * поисковых запросов
 */
class RequestsQuery extends Controller
{
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
     * Экземпляр даннх пользователя
     * 
     * @var \App\Http\Controllers\Users\UserData
     */
    protected $user;

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

        $this->filter->start = $request->start ?? date("Y-m-d");
        $this->filter->stop = $request->stop ?? date("Y-m-d");

        $this->tab = $request->tab;
    }

    /**
     * Формирование запроса и применение фильтров
     * 
     * @return \App\Models\RequestsRow
     */
    public function where()
    {
        $this->setWhere()
            ->setDatesFilter()
            ->setOptionsTabFilter()
            ->setUserPermitsFilter()
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

                        $column = $attr['column'] ?? null; // Наименование колонки
                        $operator = $attr['operator'] ?? null; // Оператор условия

                        $value = $attr['value'] ?? null; // Строчное значение
                        $between = $attr['between'] ?? null; // Период значений от и до
                        $list = $attr['list'] ?? null; // Список значений

                        // Необходимо наименование колонки
                        if ($column) {

                            $row = null;

                            // Разделение на типы аргументов
                            if (in_array($method, $this->whereOneArguments))
                                $row = [$column];
                            elseif ($value)
                                $row = [$column, $operator ?? $value, $operator ? $value : null];
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
                            : $query = $model->$method($where);
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
        if (!$this->tab->date_types ?? null)
            return $this->setDefaultDatesFilter();

        $set = false; # Флаг примененного фильтра

        if (is_array($this->tab->date_types)) {

            $this->model = $this->model->where(function ($query) use (&$set) {

                foreach ($this->tab->date_types as $type => $bool) {

                    if ($bool and in_array($type, $this->dates_columns)) {

                        $set = true; # Флаг примененного фильтра

                        $query->orWhere(function ($query) use ($type) {
                            $query->whereDate($type, '>=', $this->filter->start)
                                ->whereDate($type, '<=', $this->filter->stop);
                        });
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
        $this->model = $this->model->whereDate('created_at', '>=', $this->filter->start)
            ->whereDate('created_at', '<=', $this->filter->stop);

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

        $sector = $this->user->checkedPermits()->requests_all_my_sector;
        $sectors = $this->user->checkedPermits()->requests_all_sectors;
        $callcenters = $this->user->checkedPermits()->requests_all_callcenters;
        
        $my = $this->tab->request_all == "my" or $this->tab->request_all === null;
        $mySector = $this->tab->request_all == "sector";
        $myCallcenter = $this->tab->request_all == "callcenter";
        $myAll = $this->tab->request_all == "all";

        // Вывод всех заявок
        if ($myAll or $callcenters)
            return $this;

        // Ввывод заявок всего коллцентра
        if ($myCallcenter or $sectors) {

            $this->model = $this->model->where(function ($query) {
                $query->where([
                    ['callcenter_sector', $this->user->getAllSectors()],
                    ['callcenter_sector', '!=', null],
                ])
                    ->orWhere('pin', $this->user->pin);
            });

            return $this;
        }

        // Вывод всех заявок сектора
        if ($mySector or $sector) {
            $this->model = $this->model->where(function ($query) {
                $query->where([
                    ['callcenter_sector', $this->user->callcenter_sector_id],
                    ['callcenter_sector', '!=', null],
                ])
                    ->orWhere('pin', $this->user->pin);
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
            $query->where([
                ['callcenter_sector', $this->user->callcenter_sector_id],
                ['callcenter_sector', '!=', null],
            ])
                ->orWhere('pin', $this->user->pin);
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
            $query->where([
                ['callcenter_sector', $this->user->getAllSectors()],
                ['callcenter_sector', '!=', null],
            ])
                ->orWhere('pin', $this->user->pin);
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
}
