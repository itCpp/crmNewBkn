<?php

namespace App\Http\Controllers\Ratings\Data;

use App\Models\User;
use App\Models\UsersPosition;

/**
 * @method findUsers()
 * @method getTemplateUserRow()
 * @method userRowDateTemplate()
 */
trait Users
{
    /**
     * Наименования секторов
     * 
     * @var array
     */
    protected $sectors = [];

    /**
     * Должности сотрудников
     * 
     * @var array<\App\Models\UsersPosition>
     */
    protected $posiitons = [];

    /**
     * Массив руководителей
     * 
     * @var array
     */
    protected $admins = [];

    /**
     * Поиск руководителей и админов секторов
     * 
     * @return $this
     */
    public function findAdmins()
    {
        if (!request()->user()->can('rating_show_admins'))
            return $this;

        $this->admins = User::select('pin')
            ->whereIn('position_id', $this->envExplode('RATING_ADMIN_POSITION_ID'))
            ->get()
            ->map(function ($row) {
                return $row->pin;
            })
            ->toArray();

        return $this;
    }

    /**
     * Поиск сотрудников
     * 
     * @param array $pins
     * @return $this
     */
    public function findUsers($pins = [])
    {
        $pins = array_unique([...$pins, ...$this->data->pins]);
        $this->data->newToOld = collect([]);

        User::where(function ($query) use ($pins) {
            $query->whereIn('pin', $pins)
                ->orWhereIn('old_pin', $pins);
        })
            ->when(count($this->admins) > 0, function ($query) {
                $query->orWhereIn('pin', $this->admins);
            })
            ->get()
            ->map(function ($row) use (&$users) {

                $row->position = $this->getPositionName($row->position_id);

                $this->data->newToOld[$row->pin] = $row->old_pin;
                $users[$row->pin] = $this->getTemplateUserRow($row);

                return $row;
            });

        $this->data->pins = collect($users ?? []);

        return $this;
    }

    /**
     * Шаблон строки рейтинга сотрудника
     * 
     * @param \App\Models\User $row
     * @return object
     */
    public function getTemplateUserRow($row)
    {
        $name = $row->surname;
        $name .= " " . $row->name;
        $name .= " " . $row->patronymic;

        $template = [
            'bonuses' => 0, # Общая сумма бонусов
            'bonus_cahsbox' => 0, # Бонус кассы
            'bonus_comings' => 0, # Сумма бонусов за приходы
            'cahsbox' => 0, # Касса по приходам оператора
            'color' => null, # Цвет блока на странице рейтинга
            'callcenter_id' => $row->callcenter_id,
            'callcenter_sector_id' => $row->callcenter_sector_id,
            'coming_one_pay' => 0, # Сумма за один приход
            'comings' => 0, # Количество приходов
            'comings_in_day' => 0, # Количество приходов в день
            'comings_sum' => 0, # Сумма за приходы
            'dates' => [], # Подробные данные по кажому дню
            'efficiency' => 0, # КПД
            'fio' => preg_replace('~^(\S++)\s++(\S)\S++\s++(\S)\S++$~u', '$1 $2.$3.', trim($name)),
            'load' => 0, # Нагрузка
            'name' => trim($name),
            'oklad' => 0, # Оклад за месяц
            'pin' => $row->pin ?? null,
            'pinOld' => $this->data->newToOld[$row->pin] ?? null,
            'place' => 0, # Место в рейтинге
            'position' => $row->position,
            'requests' => 0, # Количество заявок для расчета
            'requestsAll' => 0, # Общее количество заявок
            'salary' => 0, # Итоговая сумма по рейтингу
            'sector' => $this->getSectorName($row), # Данные сектора сотруднкиа
            'working' => $row->deleted_at === null, # Идентификатор уволнения
            'row' => $row->toArray(),
        ];

        return (object) $template;
    }

    /**
     * Поиск имени сектора сотрудника
     * 
     * @param \App\Models\User $row
     * @return string|null
     */
    public function getSectorName($row)
    {
        if (!$row->callcenter_sector_id)
            return null;

        $id = $row->callcenter_sector_id;

        if (!empty($this->sectors[$id]))
            return $this->sectors[$id];

        $this->sectors[$id] = $row->sector ? $row->sector->toArray() : null;

        return $this->sectors[$id];
    }

    /**
     * Должности сотрудников
     * 
     * @param null|int $position_id
     * @return null|string
     */
    public function getPositionName($position_id)
    {
        if (!$position_id)
            return null;

        if (!empty($this->positions[$position_id]))
            return $this->positions[$position_id]->name ?? null;

        $this->positions[$position_id] = UsersPosition::find($position_id);

        return $this->positions[$position_id]->name ?? null;
    }

    /**
     * Шаблон статистики сотрудника за один день
     * 
     * @param string $date
     * @return object
     */
    public function userRowDateTemplate($date)
    {
        return (object) [
            'date' => $date,
            'timestamp' => strtotime($date),
            'comings' => 0, // Количество приходов
            'bonus_comings' => 0, // Бонусы за приходы в день
            'cahsbox' => 0, // Сумма с заключенных договоров
            'requests' => 0,
            'requestsAll' => 0,
            'efficiency' => 0, # КПД
        ];
    }
}
