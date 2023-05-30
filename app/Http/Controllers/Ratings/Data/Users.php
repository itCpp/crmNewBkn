<?php

namespace App\Http\Controllers\Ratings\Data;

use App\Models\RatingGlobalData;
use App\Models\Saratov\Personal;
use App\Models\Saratov\PersonalOkladStory;
use App\Models\User;
use App\Models\UsersPosition;
use App\Models\UsersPositionsStory;

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
     * Массив руководителей колл-центров
     * 
     * @var array<int>
     */
    protected $сhiefs = [];

    /**
     * Массив руководителей секторов
     * 
     * @var array<int>
     */
    protected $admins = [];

    /**
     * Поиск руководителей колл-центров
     * 
     * @return $this
     */
    public function findChiefs()
    {
        if (!request()->user()->can('rating_show_chiefs'))
            return $this;

        $this->сhiefs = User::select('pin')
            ->whereIn('position_id', $this->envExplode('RATING_TRAINER_POSITINS_ID'))
            ->where(function ($query) {
                $query->where('deleted_at', null)
                    ->orWhere('deleted_at', '>', $this->dates->start . " 00:00:00");
            })
            ->get()
            ->map(function ($row) {
                return $row->pin;
            })
            ->toArray();

        return $this;
    }

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
            ->where(function ($query) {
                $query->where('deleted_at', null)
                    ->orWhere('deleted_at', '>', $this->dates->start . " 00:00:00");
            })
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
        $this->findChiefs()
            ->findAdmins();

        $pins = array_unique([
            ...$pins,
            ...$this->data->pins,
            ...$this->admins,
            ...$this->сhiefs,
        ]);

        $user = optional($this->request->user());

        if ($user->pin and $user->can('requests_pin_for_appointment'))
            $pins[] = $user->pin;

        $this->data->pin_list = array_values($pins);
        $this->data->newToOld = [];

        User::where(function ($query) use ($pins) {
            $query->whereIn('pin', $pins)
                ->orWhereIn('old_pin', $pins);
        })
            ->get()
            ->map(function ($row) use (&$rows) {

                $row->position = $this->getPositionName($row->position_id);

                if ($row->old_pin and $row->old_pin != $row->pin)
                    $this->data->newToOld[$row->pin] = $row->old_pin;

                $rows[$row->pin] = $row;

                return $row;
            });

        foreach ($pins ?? [] as $pin) {

            $row = null;
            $pin = (int) $pin;

            if (isset($rows[$pin])) {
                $row = $rows[$pin] ?? null;
            } else if ($key = array_search($pin, $this->data->newToOld)) {
                $pin = $key;
                $row = $rows[$pin] ?? null;
            }

            if (!$row) {
                $row = new User;
                $row->pin = $pin;
                $row->name = "Неизвестно";
                $row->deleted_at = true;
            }

            if (isset($users[$pin]))
                continue;

            $users[$pin] = $this->getTemplateUserRow($row);
        }

        $this->getGlobalRatingStats($pins);

        $this->data->pins = collect($users ?? []);

        $this->getStory();

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
            'id' => $row->id,
            'bonuses' => 0, # Общая сумма бонусов
            'bonus_cahsbox' => 0, # Бонус кассы
            'bonus_comings' => 0, # Сумма бонусов за приходы
            'cahsbox' => 0, # Касса по приходам оператора
            'color' => null, # Цвет блока на странице рейтинга
            'callcenter_id' => $row->callcenter_id ?? 0,
            'callcenter_sector_id' => $row->callcenter_sector_id ?? 0,
            'coming_one_pay' => 0, # Сумма за один приход
            'comings' => 0, # Количество приходов
            'comings_in_day' => 0, # Количество приходов в день
            'comings_sum' => 0, # Сумма за приходы
            'dates' => [], # Подробные данные по кажому дню
            'drains' => 0, # Количество сливов
            'efficiency' => 0, # КПД
            'efficiency_agreement' => 0, # КПД договора
            'fines' => 0, # Сумма ЦРэМочных штрафов сотрудника
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
            // 'row' => $row->toArray(),
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
            'drains' => 0, # Еоличество сливов
        ];
    }

    /**
     * Поиск истории сотрудников
     * 
     * @return $this
     */
    public function getStory()
    {
        $pins = $this->data->pins->map(function ($row) {
            return $row->pinOld ?: $row->pin;
        })->toArray();

        $ids = $this->data->pins->map(function ($row) {
            return $row->id;
        })->toArray();

        /** Информация о сотрудниках */
        Personal::whereIn('pin', $pins)
            ->get()
            ->each(function ($row) {
                $this->data->stories->personal[$row->pin] = $row;
            });

        /** История смены оклада */
        PersonalOkladStory::whereIn('pin', $pins)
            ->where('delDate', null)
            ->get()
            ->each(function ($row) {
                $this->data->stories->oklad[$row->pin][] = (object) [
                    'date' => $row->date,
                    'new' => $row->oklad,
                    'old' => $row->okladOld,
                ];
            });

        /** История смены должности */
        UsersPositionsStory::whereIn('user_id', $ids)
            ->get()
            ->each(function ($row) {
                $this->data->stories->position[$row->user_id][] = (object) [
                    'date' => date("Y-m-d", strtotime($row->created_at)),
                    'new' => $row->position_new,
                    'old' => $row->position_old,
                ];
            });

        // /** Добавление сотрудников, пониженных в должности */
        // if (request()->user()->can('rating_show_chiefs')) {
        //     foreach ($this->data->stories->position as $position) {
        //     }
        // }

        return $this;
    }

    /**
     * Дполнительная информация по глобальным статистическим данным
     * 
     * @return $this
     */
    public function getGlobalRatingStats($pins = [])
    {
        RatingGlobalData::whereIn('pin', $pins)
            ->get()
            ->each(function ($row) {

                if ($row->requests_moscow > 0)
                    $row->efficiency = round(($row->comings / $row->requests_moscow) * 100, 2);

                if ($row->comings > 0)
                    $row->efficiency_agreement = round(($row->agreements_firsts / $row->comings) * 100, 2);

                $this->data->rating_global[$row->pin] = $row;
            });

        return $this;
    }
}
