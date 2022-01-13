<?php

namespace App\Http\Controllers\Ratings\Data;

use App\Models\User;

/**
 * @method findUsers()
 * @method getTemplateUserRow()
 * @method userRowDateTemplate()
 */
trait Users
{
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
            ->get()
            ->map(function ($row) use (&$users) {

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
            'pin' => $row->pin ?? null,
            'pinOld' => $this->data->newToOld[$row->pin] ?? null,
            'name' => trim($name),
            'fio' => preg_replace('~^(\S++)\s++(\S)\S++\s++(\S)\S++$~u', '$1 $2.$3.', trim($name)),
            'oklad' => 0, # Оклад за месяц
            'comings' => 0, # Количество приходов
            'comings_summa' => 0, # Сумма за приходы
            'requests' => 0, # Количество заявок для расчета
            'requestsAll' => 0, # Общее количество заявок
            'kpd' => 0, # КПД
            'position' => 0, # Место в рейтинге
            'load' => 0, # Нагрузка
            'cahsbox' => 0, # Касса по приходам оператора
            'itogo' => 0, # Итоговая сумма по рейтингу
            'dates' => [], # Подробные данные по кажому дню
            'bonus_cahsbox' => 0, # Бонус кассы
            'bonus_comings' => 0, # Сумма бонусов за приходы
        ];

        return (object) $template;
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
            'cahsbox' => 0, // Сумма с заключенных договоров
            'requests' => 0,
            'requestsAll' => 0,
        ];
    }
}
