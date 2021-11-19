<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Schema;

class RequestsRow extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'query_type',
        'callcenter_sector',
        'pin',
        'last_phone',
        'source_id',
        'sourse_resource',
        'client_name',
        'theme',
        'region',
        'check_moscow',
        'comment',
        'comment_urist',
        'comment_first',
        'status_id',
        'status_icon',
        'address',
        'event_at',
        'uplift',
        'uplift_at',
    ];

    /**
     * Поля типа Carbon
     * 
     * @var array
     */
    protected $dates = [
        'event_at',
        'created_at',
        'deleted_at',
        'uplift_at',
    ];

    /**
     * Клиенты, относящиеся к заявке
     * 
     * @return \App\Models\RequestRow
     */
    public function clients()
    {
        return $this->belongsToMany(RequestsClient::class, 'requests_rows_requests_clients', 'id_request', 'id_requests_clients');
    }

    /**
     * Источник заявки
     * 
     * @return 
     */
    public function source()
    {
        return $this->belongsTo(RequestsSource::class, 'source_id');
    }

    /**
     * Источник заявки
     * 
     * @return 
     */
    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    /**
     * Адрес записи
     * 
     * @return 
     */
    public function office()
    {
        return $this->belongsTo(Office::class, 'address');
    }

    /**
     * Отношение сктора к заявке
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sector()
    {
        return $this->belongsTo(CallcenterSector::class, 'callcenter_sector');
    }

    /**
     * Отношения заявки к смс сообшения
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sms()
    {
        return $this->belongsToMany(SmsMessage::class, 'sms_request', 'request_id', 'sms_id');
    }

    /**
     * Вывод информации по колонкам
     * 
     * @return array
     */
    static function getColumnsList()
    {
        $model = new static;

        return DB::table('INFORMATION_SCHEMA.COLUMNS')
            ->select(
                'COLUMN_NAME as name',
                'COLUMN_TYPE as type',
                'COLUMN_COMMENT as comment'
            )
            ->where('table_name', $model->getTable())
            ->get();
    }

    /**
     * Формирование запроса для вывода вкладки
     * 
     * @param array         Данные для конструктора запроса
     * @return RequestRow   Модель с примененным условием запроса
     */
    public static function setWhere($wheres = [])
    {
        /**
         * Определение типов переменных для конструктора запросов
         * 
         * @var array
         */
        $typeWhereValue = [
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
        $whereArgumentsArray = [
            'where',
            'orWhere'
        ];

        /**
         * Методы конструктора, принимающице только один аргумент
         * 
         * @var array
         */
        $whereOneArguments = [
            'whereNull',
            'whereNotNull',
            'orWhereNull',
            'orWhereNotNull'
        ];

        $model = with(new static);

        foreach ($wheres as $query) {

            $method = $query['where']; // Методы выражения

            // Условия сортировки
            if ($method == "orderBy") {

                $column = $query['column'] ?? null;
                $by = $query['by'] ?? null;
                $order_by = [];

                if ($column)
                    $order_by[] = $column;

                if ($column && in_array($by, ["ASC", "DESC"]))
                    $order_by[] = $by;

                if (count($order_by))
                    $model = $model->orderBy(...$order_by);
            }
            // Методы для формирования простых условий
            elseif ($method != "whereFunction") {

                $attrts = $query['attr'] ?? []; // Список атрибутов

                // Метод может принимать первым аргументом массив из множества условий
                $toArray = in_array($method, $whereArgumentsArray);

                $where = []; // Массив условий

                // Обход аргументов
                foreach ($attrts as $attr) {

                    $column = $attr['column'] ?? null; // Наименование колонки
                    $operator = $attr['operator'] ?? null; // Оператор условия

                    $value = $attr['value'] ?? null; // Строчное значение
                    $between = $attr['between'] ?? null; // Период значений от и до
                    $list = $attr['list'] ?? null; // Список значений

                    // Определение типа значений для различных условий
                    $type = $typeWhereValue[$method] ?? "value";

                    // Необходимо наименование колонки
                    if ($column) {

                        $row = null;

                        // Разделение на типы аргументов
                        if (in_array($method, $whereOneArguments))
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

                    $model = $arguments == 1
                        ? $model->$method(...$where[0] ?? [])
                        : $model = $model->$method($where);
                }
            }
        }

        return $model;
    }
}
