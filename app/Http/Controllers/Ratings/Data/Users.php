<?php

namespace App\Http\Controllers\Ratings\Data;

use App\Models\User;

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

        User::where(function ($query) use ($pins) {
            $query->whereIn('pin', $pins)
                ->orWhereIn('old_pin', $pins);
        })
            ->get()
            ->map(function ($row) use (&$users, &$old_to_new) {

                $old_to_new[$row->old_pin] = $row->pin;
                $users[$row->pin] = $this->getTemplateUserRow($row);

                return $row;
            });

        $this->data->pins = collect($users ?? []);
        $this->data->old_to_new = collect($old_to_new ?? []);

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
            'name' => trim($name),
            'fio' => preg_replace('~^(\S++)\s++(\S)\S++\s++(\S)\S++$~u', '$1 $2.$3.', trim($name)),
            'oklad' => 0, # Оклад за месяц
            'comings' => 0, # Количество приходов
            'comings_summa' => 0, # Сумма за приходы
            'comings_summa_bonus' => 0, # Сумма бонусов за приходы
            'requests' => 0, # Количество заявок для расчета
            'requestsAll' => 0, # Общее количество заявок
            'kpd' => 0, # КПД
            'position' => 0, # Место в рейтинге
            'load' => 0, # Нагрузка
            'kassa' => 0, # Кассв по приходам оператора
            'itogo' => 0, # Итоговая сумма по рейтингу
        ];

        return (object) $template;
    }
}
