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
            'icon' => "calendar outline",
            'iconColor' => null,
        ],
        [
            'name' => 'recordsDay',
            'title' => "Записи, сделанные за текущий день",
            'icon' => "calendar check outline",
            'iconColor' => "green",
        ],
        [
            'name' => 'recordsInDay',
            'title' => "Записи день в день",
            'icon' => "checked calendar",
            'iconColor' => "green",
        ],
        [
            'name' => 'recordsNextDay',
            'title' => "Записи на следующий день, из сегодняшних заявок",
            'icon' => "calendar plus outline",
            'iconColor' => "blue",
        ],
        [
            'name' => 'recordsToDay',
            'title' => "Записи на завтра",
            'icon' => "calendar plus outline",
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
            'iconColor' => "red",
        ],
        [
            'name' => 'efficiency',
            'title' => "КПД",
            'icon' => "percent",
            'iconColor' => null,
        ],
    ];
}
