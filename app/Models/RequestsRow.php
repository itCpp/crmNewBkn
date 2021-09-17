<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Support\Facades\Schema;

class RequestsRow extends Model
{
    use HasFactory;

    /**
     * Вывод информации по колонкам
     * 
     * @return array
     */
    public static function getColumnsList() {

        $model = with(new static);
    
        return \DB::table('INFORMATION_SCHEMA.COLUMNS')
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
            'whereNotNull'
        ];

        $model = with(new static);

        foreach ($wheres as $query) {

            $method = $query['where']; // Методы выражения
            
            // Методы для формирования простых условий
            if ($method != "whereFunction") {

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
                        if ($value)
                            $row = [$column, $operator ?? $value, $operator ? $value : null];
                        elseif (is_array($between) AND count($between) == 2)
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
