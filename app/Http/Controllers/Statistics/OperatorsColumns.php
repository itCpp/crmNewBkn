<?php

namespace App\Http\Controllers\Statistics;

trait OperatorsColumns
{
    /**
     * Данные по колонкам отображаемой таблицы
     * 
     * @var array
     */
    protected $columns = [
        [
            'name' => 'requests',
            'title' => "Всего заявок сегодня",
            'icon' => "list alternate",
            'iconColor' => null,
        ],
        [
            'name' => 'comings',
            'title' => "Приходы",
            'icon' => "child",
            'iconColor' => null,
        ],
        [
            'name' => 'comingsInDay',
            'title' => "Приходы день в день",
            'icon' => "child",
            'iconColor' => "green",
        ],
        [
            'name' => 'records',
            'title' => "Записи на сегодня",
            'icon' => "edit",
            'iconColor' => null,
        ],
        [
            'name' => 'recordsDay',
            'title' => "Записи, сделанные за текущий день",
            'icon' => "edit",
            'iconColor' => "red",
        ],
        [
            'name' => 'recordsInDay',
            'title' => "Записи день в день",
            'icon' => "edit",
            'iconColor' => "green",
        ],
        [
            'name' => 'recordsNextDay',
            'title' => "Записи на следующий день",
            'icon' => "edit outline",
            'iconColor' => "blue",
        ],
        [
            'name' => 'recordsToDay',
            'title' => "Записи на завтра",
            'icon' => "edit outline",
            'iconColor' => "orange",
        ],
        [
            'name' => 'notRinging',
            'title' => "Недозвоны",
            'icon' => "call square",
            'iconColor' => "red",
        ],
        [
            'name' => 'drain',
            'title' => "Сливы",
            'icon' => "bath",
            'iconColor' => "blue",
        ],
        [
            'name' => 'efficiency',
            'title' => "КПД",
            'icon' => "percent",
            'iconColor' => null,
        ],
    ];
}
