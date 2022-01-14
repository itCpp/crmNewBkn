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

        $this->calcGeneralStats();

        $this->pushChiefRating()
            ->pushAdminRating();

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
        $this->row = $row;

        $this->callcenter_id = $row->callcenter_id;
        $this->sector_id = $row->callcenter_sector_id;

        $this->checkStatsRow()
            ->setComings()
            ->setRequests()
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
        if (!$requests = ($this->data->requests[$this->row->pin] ?? null))
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

        // Количество приходов в день
        if ($this->dates->diff > 0)
            $row->comings_in_day = round($row->comings / $this->dates->diff, 1);

        // Премия за кассу
        $row->bonus_cahsbox = $row->cahsbox >= 1000000
            ? floor($row->cahsbox / 500000) * 2500
            : 0;

        // ЗП за приходы
        $row->coming_one_pay = $this->getOneComingSumPay();
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

        return $this;
    }

    /**
     * Добавляет данные для руководителей колл-центров
     * 
     * @return $this
     */
    public function pushChiefRating()
    {
        foreach ($this->users as &$user) {

            if (!in_array($user->pin, $this->сhiefs))
                continue;

            $user->color = $this->getColorAdmin($user);
        }

        return $this;
    }

    /**
     * Добавляет данные для руководителей секторов
     * 
     * @return $this
     */
    public function pushAdminRating()
    {
        foreach ($this->users as &$user) {

            if (!in_array($user->pin, $this->admins))
                continue;

            $callcenter = $user->callcenter_id;
            $sector = $user->callcenter_sector_id;
            $stat = $this->data->stats[$callcenter]->sectors[$sector] ?? null;

            $user->admin = $stat;
            $user->admin_coming_one_pay = $this->getAdminPercent($user);
            $user->admin_comings_sum = $user->admin_coming_one_pay * ($stat->comings ?? 0);

            $user->admin_bonus_cashbox = $this->getCashboxMonthPercent();

            $user->admin_bonus = $user->admin_bonus_cashbox;
            $user->salary += $user->admin_comings_sum;

            $user->color = $this->getColorAdmin($user);
        }

        return $this;
    }

    /**
     * Подсчет общей статистики колл-центров
     * 
     * @return $this
     */
    public function calcGeneralStats()
    {
        foreach ($this->data->stats as &$callcenter) {

            foreach ($callcenter->sectors as &$sector) {

                $callcenter->cahsbox += $sector->cahsbox;
                $callcenter->comings += $sector->comings;
                $callcenter->requests += $sector->requests;
                $callcenter->requestsAll += $sector->requestsAll;

                if ($sector->requests > 0)
                    $sector->efficiency = round(($sector->comings / $sector->requests) * 100, 2);

                $sector->dates = $this->setDatesArray($sector->dates);

                foreach ($sector->dates as &$row) {

                    if ($row->requests > 0)
                        $row->efficiency = round(($row->comings / $row->requests) * 100, 2);

                    if (empty($callcenter->dates[$row->date]))
                        $callcenter->dates[$row->date] = $this->userRowDateTemplate($row->date);

                    $callcenter->dates[$row->date]->cahsbox += $row->cahsbox;
                    $callcenter->dates[$row->date]->comings += $row->comings;
                    $callcenter->dates[$row->date]->requests += $row->requests;
                    $callcenter->dates[$row->date]->requestsAll += $row->requestsAll;
                }
            }

            $callcenter->dates = $this->setDatesArray($callcenter->dates);

            foreach ($callcenter->dates as &$row) {

                if ($row->requests > 0)
                    $row->efficiency = round(($row->comings / $row->requests) * 100, 2);
            }
        }

        return $this;
    }

    /**
     * Расчет суммы за приход
     * 
     * @return int
     */
    public function getOneComingSumPay()
    {
        /**
         * Lera, [01.12.2021 17:59]
         * [Переслано от Елена Ельнова]
         * с этого периода если возможно, то поставить мотивацию: до 35 приходов- 200 рублей
         * за человека, от 35 до 44 - 300 рублей, и 45 и более 400 рублей, 55 или 60 приходов - 500
         * рублей, это уже как вы скажете
         * 
         * Lera, [01.12.2021 17:59]
         * ЭТО КОЛЛ САРАТОВ
         * 
         * Lera, [02.12.2021 8:41]
         * [В ответ на Lera]
         * Только это уже с 16-30.11-т.е. с прошлого периода
         */
        if ($this->dates->start >= "2021-11-16") {

            if ($this->row->comings >= 60)
                return 500;
            else if ($this->row->comings >= 45)
                return 400;
            else if ($this->row->comings >= 35)
                return 300;
            else
                return 200;
        }

        return 200;
    }

    /**
     * Метод определения ставки для руководителя за один приход его сектора
     * 
     * * Антон Суханов, [15.12.20 11:33]
     * [Переслано от Daria]
     * Менеджеры (руководители отделов) - ДЕНЬ
     * 1 приход - 50 руб.
     * При выполнении КПД приходы перерасчитываются:
     * 21% КПД - 60 руб. приход
     * 22% КПД 70 руб. приход
     * и тд, до 30% КПД, прибавляется 10 рублей за приход.
     * 
     * * Антон Суханов, [15.12.20 11:33]
     * [Переслано от Daria]
     * РУКОВОДИТЕЛЬ (НОЧНАЯ СМЕНА)
     * 1 приход - 100 руб.При выполнении КПД приходы перерасчитываются:
     * 21% КПД - 110 руб. приход
     * 22% КПД 120 руб. приход
     * и тд, до 30% КПД, прибавляется 10 рублей за приход.
     * 
     * @param object $row Объект данных сотрудника
     * @return int
     */
    public function getAdminPercent($row)
    {
        $efficiency = $row->admin->efficiency ?? 0;

        if ($efficiency >= 30)
            return 150;
        elseif ($efficiency >= 29)
            return 140;
        elseif ($efficiency >= 28)
            return 130;
        elseif ($efficiency >= 27)
            return 120;
        elseif ($efficiency >= 26)
            return 110;
        elseif ($efficiency >= 25)
            return 100;
        elseif ($efficiency >= 24)
            return 90;
        elseif ($efficiency >= 23)
            return 80;
        elseif ($efficiency >= 22)
            return 70;
        elseif ($efficiency >= 21)
            return 60;

        return 50;
    }

    /**
     * Метод расчета премии руководителям в конце месяца
     * 
     * @param null|int $sum Сумма кассы
     * @return int
     */
    public function getCashboxMonthPercent($sum = null)
    {
        // Расчет премии от кассы происходит в последнем периоде месяца
        if ($this->dates->stopPeriod != $this->dates->stopMonth)
            return 0;

        $sum = $sum ?: ($this->cashbox->sum->month ?? 0);

        if ($sum >= 26000000)
            return 20000;
        elseif ($sum >= 25000000)
            return 17500;
        elseif ($sum >= 24000000)
            return 15000;
        elseif ($sum >= 23000000)
            return 12500;
        elseif ($sum >= 22000000)
            return 10000;
        elseif ($sum >= 21000000)
            return 7500;
        elseif ($sum >= 20000000)
            return 5000;

        return 0;
    }

    /**
     * Бонус за приходы в один день
     * 
     * @param int $comings
     * @return int
     */
    public function getComingsBonusFromOneDay($comings = 0)
    {
        if (!$comings)
            return 0;

        $count = 5; // Количество приходов для выплаты одного бонуса
        $price = 250; // Сумма за выполненное количество приходов

        return floor($comings / $count) * $price;
    }

    /**
     * Определение цвета блока на страницу рейтинга
     * 
     * @return string
     */
    public function getColor()
    {
        if (!$this->row->working)
            return "gray";

        if ($this->dates->start >= "2021-07-01") {

            if ($this->row->efficiency >= 25)
                return "green";
            elseif ($this->row->efficiency >= 20)
                return "yellow";
        }

        return "red";
    }

    /**
     * Определяет цвет блока для руководителя или админа
     * 
     * @param object $row
     * @return string
     */
    public function getColorAdmin($row)
    {
        if (in_array($row->pin, $this->admins))
            return "blue";
        else if (in_array($row->pin, $this->сhiefs))
            return "white";

        return $row->color;
    }
}
