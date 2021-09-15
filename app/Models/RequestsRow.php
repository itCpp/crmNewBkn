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

        $model = with(new static);

        foreach ($wheres as $query) {

            $method = $query['where'];
            
            if ($method != "whereFunction") {

                $attrts = $query['attr'] ?? [];
                $where = [];

                foreach ($attrts as $attr) {

                    $column = $attr['column'] ?? null;
                    $operator = $attr['operator'] ?? null;
                    $value = $attr['value'] ?? null;
                    $value0 = $attr['value0'] ?? null;
                    $value1 = $attr['value1'] ?? null;

                    if ($value0 AND $value1) {
                        $row = [$column, [$value0, $value1]];
                    }
                    else {
                        $row = [$column, $operator ?? $value, $operator ? $value : null];
                    }

                    $where[] = $row;

                }

                if (count($where) == 1)
                    $model = $model->$method(...$where[0]);
                else
                    $model = $model->$method($where);

            }

        }

        return $model;

    }

}
