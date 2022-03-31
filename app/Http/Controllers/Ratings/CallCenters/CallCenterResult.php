<?php

namespace App\Http\Controllers\Ratings\CallCenters;

use App\Http\Controllers\Ratings\CallCenters;

/**
 * @method \App\Http\Controllers\Ratings\Data\Users userRowDateTemplate()
 *
 * @method getResult()
 * @method getResultRow()
 * @method setComings()
 * @method findComingsData()
 * @method setRequests()
 */
trait CallCenterResult
{
    use Getters;

    /**
     * Экземпляр объекта данных обрабатываемого сотрудника
     * 
     * @var object
     */
    protected $row;

    /**
     * Идентификатор колл-центра сотрудника
     * 
     * @var int|null
     */
    protected $callcenter_id = null;

    /**
     * Идентификатор сектора сотрудника
     * 
     * @var int|null
     */
    protected $sector_id = null;

    /**
     * Порядок сортировки по цветам блока
     * 
     * @var array
     */
    protected $color_sorted = [
        'white', 'blue', 'green', 'yellow', 'red', 'gray'
    ];

    /**
     * Атрибуты, которые необходимо сложить в статистике
     * 
     * @var array
     */
    protected $attributes_for_sum_stats = [
        'cahsbox', 'comings', 'requests', 'requestsAll', 'drains'
    ];

    /**
     * Подсчет данных основного рейтинга
     * 
     * @return $this
     */
    public function getResult()
    {
        $this->users = collect([]);

        foreach ($this->data->pins as $row) {
            $this->users->push($this->getResultRow($row));
        }

        $this->calcGeneralStats()
            ->appendLeaderRating();

        $places = [];

        $sorted = $this->users->sortByDesc('efficiency')
            ->each(function ($row) use (&$places) {
                $places[] = $row->efficiency;
            })
            ->sortByDesc('comings')
            ->sortBy(function ($user) {
                return array_search($user->color, $this->color_sorted);
            });

        $places = array_unique($places);

        $this->data->users = $sorted->values()
            ->map(function ($row) use ($places) {

                $place = array_search($row->efficiency, $places);
                $row->place = $place !== null ? ($place + 1) : 0;

                return $row;
            })
            ->all();

        return $this;
    }

    /**
     * Рейтинг одного сотрудника
     * 
     * @param object $row
     * @return object
     */
    public function getResultRow($row)
    {
        $row->oklads = [];

        /** История изменений оклада */
        if (isset($this->data->stories->oklad[$row->pin]))
            $row->oklads = $this->data->stories->oklad[$row->pin];
        else if (isset($this->data->stories->oklad[$row->pinOld]))
            $row->oklads = $this->data->stories->oklad[$row->pinOld];

        $row->oklads = $row->oklads ?? [];
        $row->oklad_start = null;

        foreach ($row->oklads as $oklad) {
            $row->oklad = $oklad->new;
            $row->oklad_start = $oklad->date;
        }

        /** История смены должности */
        $row->positions = $this->data->stories->position[$row->id] ?? [];
        $row->position_id = null;
        $row->position_start = null;

        foreach ($row->positions as $position) {
            $row->position_id = $position->new;
            $row->position_start = $position->date;
        }

        $row->position = $this->getPositionName($row->position_id);

        $this->row = $row;

        $this->callcenter_id = $row->callcenter_id;
        $this->sector_id = $row->callcenter_sector_id;

        $this->setPersonalData()
            ->checkStatsRow()
            ->setComings()
            ->setRequests()
            ->setDrains()
            ->setAgreements()
            ->setCashbox()
            ->setResult();

        return $this->row;
    }

    /**
     * Проверяет наличие статистической строки
     * 
     * @return $this;
     */
    public function checkStatsRow()
    {
        $stats = &$this->data->stats;

        $callcenter = $this->callcenter_id;
        $sector = $this->sector_id;

        if (empty($stats[$callcenter]))
            $stats[$callcenter] = $this->getTemplateStatsRow();

        if (empty($stats[$callcenter]->sectors[$sector]))
            $stats[$callcenter]->sectors[$sector] = $this->getTemplateStatsRow(false);

        return $this;
    }

    /**
     * Подсчет договоров
     * 
     * @return $this
     */
    public function setAgreements()
    {
        $this->row->agreements = [
            'firsts' => 0,
            'seconds' => 0,
            'all' => 0,
        ];

        $this->findAgreementsData($this->row->pin)
            ->findAgreementsData($this->row->pinOld);

        return $this;
    }

    /**
     * Поиск информации о количетсве договоров
     * 
     * @param string|int|null $pin
     * @return $this
     */
    public function findAgreementsData($pin = null)
    {
        if (!isset($this->data->agreements[$pin]))
            return $this;

        $this->row->agreements['firsts'] += $this->data->agreements[$pin]['firsts'] ?? 0;
        $this->row->agreements['seconds'] += $this->data->agreements[$pin]['seconds'] ?? 0;
        $this->row->agreements['all'] += $this->data->agreements[$pin]['all'] ?? 0;

        return $this;
    }

    /**
     * Подсчет приходов
     * 
     * @return $this
     */
    public function setComings()
    {
        // Двойной вызов функции необходим при плавном переходе с одной ЦРМ на другую
        $this->findComingsData($this->row->pin)
            ->findComingsData($this->row->pinOld);

        return $this;
    }

    /**
     * Поиск данных по приходам
     * 
     * @param string|int|null $pin
     * @return $this
     */
    public function findComingsData($pin)
    {
        if (!isset($this->data->comings[$pin]))
            return $this;

        $stat = &$this->data->stats[$this->callcenter_id]->sectors[$this->sector_id];

        $this->row->comings += $this->data->comings[$pin]['count'];
        $stat->comings += $this->data->comings[$pin]['count'];

        foreach (($this->data->comings[$pin]['dates'] ?? []) as $date => $comings) {

            if (empty($this->row->dates[$date]))
                $this->row->dates[$date] = $this->userRowDateTemplate($date);

            if (empty($stat->dates[$date]))
                $stat->dates[$date] = $this->userRowDateTemplate($date);

            $this->row->dates[$date]->comings += $comings;
            $stat->dates[$date]->comings += $comings;
        }

        return $this;
    }

    /**
     * Подсчет заявок
     * 
     * @return $this
     */
    public function setRequests()
    {
        $requests = ($this->data->requests[$this->row->pin] ?? null);

        if (!$requests)
            $requests = ($this->data->requests[$this->row->pinOld] ?? null);

        if (!$requests)
            return $this;

        $stat = &$this->data->stats[$this->callcenter_id]->sectors[$this->sector_id];

        $this->row->requestsAll += $requests['all'];
        $this->row->requests += $requests['moscow'];

        $stat->requestsAll += $requests['all'];
        $stat->requests += $requests['moscow'];

        foreach (($requests['dates'] ?? []) as $date => $data) {

            if (empty($this->row->dates[$date]))
                $this->row->dates[$date] = $this->userRowDateTemplate($date);

            if (empty($stat->dates[$date]))
                $stat->dates[$date] = $this->userRowDateTemplate($date);

            $this->row->dates[$date]->requestsAll += $data['all'];
            $this->row->dates[$date]->requests += $data['moscow'];

            $stat->dates[$date]->requestsAll += $data['all'];
            $stat->dates[$date]->requests += $data['moscow'];
        }

        return $this;
    }

    /**
     * Подсчет сливов
     * 
     * @return $this
     */
    public function setDrains()
    {
        $drains = ($this->data->drains[$this->row->pin] ?? null);

        if (!$drains)
            $drains = ($this->data->drains[$this->row->pinOld] ?? null);

        if (!$drains)
            return $this;

        $stat = &$this->data->stats[$this->callcenter_id]->sectors[$this->sector_id];

        $this->row->drains += ($drains['count'] ?? 0);
        $stat->drains += ($drains['count'] ?? 0);

        foreach (($drains['dates'] ?? []) as $date => $data) {

            if (empty($this->row->dates[$date]))
                $this->row->dates[$date] = $this->userRowDateTemplate($date);

            if (empty($stat->dates[$date]))
                $stat->dates[$date] = $this->userRowDateTemplate($date);

            $this->row->dates[$date]->drains += ($data ?? 0);
            $stat->dates[$date]->drains += ($data ?? 0);
        }

        return $this;
    }

    /**
     * Применение данных кассы
     * 
     * @return $this
     */
    public function setCashbox()
    {
        $this->setCashboxForPin($this->row->pin)
            ->setCashboxForPin($this->row->pinOld);

        return $this;
    }

    /**
     * Применение данных кассы
     * 
     * @param string|int|null $pin
     * @return $this
     */
    public function setCashboxForPin($pin = null)
    {
        if (!$row = ($this->data->cahsbox->users[$pin] ?? null))
            return $this;

        $stat = &$this->data->stats[$this->callcenter_id]->sectors[$this->sector_id];

        $this->row->cahsbox += $row['sum'];
        $stat->cahsbox += $row['sum'];

        foreach (($row['dates'] ?? []) as $date => $data) {

            if (empty($this->row->dates[$date]))
                $this->row->dates[$date] = $this->userRowDateTemplate($date);

            if (empty($stat->dates[$date]))
                $stat->dates[$date] = $this->userRowDateTemplate($date);

            $this->row->dates[$date]->cahsbox += $data;
            $stat->dates[$date]->cahsbox += $data;
        }

        return $this;
    }

    /**
     * Формирование массива подробных данных за каждый расчетный день
     * 
     * @param array $data Массив c датами
     * @return array
     */
    public function setDatesArray($data = [])
    {
        $dates = collect([]);

        foreach (($data ?? []) as $row) {
            $dates->push($row);
        }

        return $dates->sortBy('timestamp')
            ->values()
            ->all();
    }

    /**
     * Итоговый расчет бонусов, окладов и тд
     * 
     * @return $this
     */
    public function setResult()
    {
        $row = &$this->row;

        $row->dates = $this->setDatesArray($row->dates);

        // Расчет КПД
        if ($row->requests)
            $row->efficiency = round(($row->comings / $row->requests) * 100, 2);

        // Расчет КПД договоров
        if (($row->agreements['firsts'] ?? 0) > 0 and $row->comings > 0) {
            $row->efficiency_agreement = round(($row->agreements['firsts'] / $row->comings) * 100, 2);
        }

        // Количество приходов в день
        if ($this->dates->diff > 0)
            $row->comings_in_day = round($row->comings / $this->dates->diff, 1);

        // Премия за кассу
        $row->bonus_cahsbox = $row->cahsbox >= 1000000
            ? floor($row->cahsbox / 500000) * 2500
            : 0;

        // ЗП за приходы
        $row->coming_one_pay = $this->getOneComingSumPay($row->comings);
        $row->comings_sum = $row->coming_one_pay * $row->comings;

        // Расчет нагрузки кассы
        if ($row->comings)
            $row->load = round($row->cahsbox / $row->comings, 2);

        // Обработка данных за каждый день
        foreach ($row->dates as &$day) {

            $bonus = $this->getComingsBonusFromOneDay($day->comings);

            $day->bonus_comings = $bonus;

            $row->bonus_comings += $bonus;
        }

        // Цвет блока на странице рейтинга
        $row->color = $this->getColor();

        // Общая сумма всех бонусов
        $row->bonuses = $row->bonus_cahsbox + $row->bonus_comings;

        // Расчет зарплаты
        $row->salary = $row->comings_sum;

        if ($row->oklad > 0) {
            $row->oklad_period = $row->oklad / 2;
            $row->salary = 0;
        }

        return $this;
    }

    /**
     * Добавление данных для руководитлей и начальничков
     * 
     * @return $this;
     */
    public function appendLeaderRating()
    {
        if (!count($this->сhiefs) and !count($this->admins))
            return $this;

        foreach ($this->users as &$user) {

            if (in_array($user->pin, $this->сhiefs))
                $user = $this->pushChiefRating($user);

            if (in_array($user->pin, $this->admins))
                $user = $this->pushAdminRating($user);
        }

        return $this;
    }

    /**
     * Добавляет данные для руководителей колл-центров
     * 
     * @param object $user
     * @return object
     */
    public function pushChiefRating($user)
    {
        $callcenter = $user->callcenter_id;
        $stat = $this->data->stats[$callcenter] ?? null;

        $user->chief = $stat;
        $user->chief_coming_one_pay = $this->getChiefPercent($stat->comings ?? 0);
        $user->chief_comings_sum = $user->chief_coming_one_pay * ($stat->comings ?? 0);

        $user->chief_bonus_cashbox = $this->getChiefCashboxPeriodPercent();

        $user->chief_bonus = $user->chief_bonus_cashbox;
        $user->salary += $user->chief_comings_sum + $user->chief_bonus_cashbox;

        $user->color = $this->getColorAdmin($user);

        return $user;
    }

    /**
     * Добавляет данные для руководителей секторов
     * 
     * @param object $user
     * @return object
     */
    public function pushAdminRating($user)
    {
        $callcenter = $user->callcenter_id;
        $sector = $user->callcenter_sector_id;
        $stat = $this->data->stats[$callcenter]->sectors[$sector] ?? null;

        $user->admin = $stat;
        $user->admin_coming_one_pay = $this->getAdminPercent($user);
        $user->admin_comings_sum = $user->admin_coming_one_pay * ($stat->comings ?? 0);

        $user->admin_bonus_cashbox = $this->getAdminCashboxMonthPercent();

        $user->admin_bonus = $user->admin_bonus_cashbox;
        $user->salary += $user->admin_comings_sum + $user->admin_bonus_cashbox;

        $user->color = $this->getColorAdmin($user);

        return $user;
    }

    /**
     * Подсчет общей статистики колл-центров
     * 
     * @return $this
     */
    public function calcGeneralStats()
    {
        $crm = $this->getTemplateStatsRow(false);

        foreach ($this->data->stats as &$callcenter) {

            foreach ($callcenter->sectors as &$sector) {

                foreach ($this->attributes_for_sum_stats as $attr) {
                    $callcenter->$attr += $sector->$attr;
                }

                if ($sector->requests > 0)
                    $sector->efficiency = round(($sector->comings / $sector->requests) * 100, 2);

                $sector->comings_in_day = round($sector->comings / $this->dates->diff, 1);

                $sector->dates = $this->setDatesArray($sector->dates);

                foreach ($sector->dates as &$row) {

                    if ($row->requests > 0)
                        $row->efficiency = round(($row->comings / $row->requests) * 100, 2);

                    if (empty($callcenter->dates[$row->date]))
                        $callcenter->dates[$row->date] = $this->userRowDateTemplate($row->date);

                    foreach ($this->attributes_for_sum_stats as $attr) {
                        $callcenter->dates[$row->date]->$attr += $row->$attr;
                    }
                }
            }

            if ($callcenter->requests > 0)
                $callcenter->efficiency = round(($callcenter->comings / $callcenter->requests) * 100, 2);

            $callcenter->comings_in_day = round($callcenter->comings / $this->dates->diff, 1);

            $callcenter->dates = $this->setDatesArray($callcenter->dates);

            foreach ($callcenter->dates as &$row) {

                if ($row->requests > 0)
                    $row->efficiency = round(($row->comings / $row->requests) * 100, 2);
            }

            foreach ($this->attributes_for_sum_stats as $attr) {
                $crm->$attr += $callcenter->$attr;
            }
        }

        if ($crm->requests > 0)
            $crm->efficiency = round(($crm->comings / $crm->requests) * 100, 2);

        $crm->comings_in_day = round($crm->comings / $this->dates->diff, 1);

        $this->data->crm = $crm;

        return $this;
    }

    /**
     * Применение информации из таблицы сотрудников
     * 
     * @return $this
     */
    public function setPersonalData()
    {
        $this->row->personal = $this->data->stories->personal[$this->row->pin] ?? null;

        if (!$this->row->personal)
            $this->row->personal = $this->data->stories->personal[$this->row->pinOld] ?? null;

        if (!$this->row->personal)
            return $this;

        if (!$this->row->id) {
            $this->row->name = $this->row->personal->fio;
            $this->row->fio = preg_replace(
                '~^(\S++)\s++(\S)\S++\s++(\S)\S++$~u',
                '$1 $2.$3.',
                trim($this->row->name)
            );
        }

        if ($this->row->oklad == 0 and $this->row->personal->oklad)
            $this->row->oklad = $this->row->personal->oklad;

        if (!$this->row->position)
            $this->row->position = $this->row->personal->doljnost;

        if (!$this->row->working)
            $this->row->working = $this->row->personal->state == "Работает";

        return $this;
    }
}
