<?php

namespace App\Http\Controllers\Ratings\CallCenters;

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
        $users = collect([]);

        foreach ($this->data->pins as $row) {
            $users->push($this->getResultRow($row));
        }

        $places = [];

        $sorted = $users->sortByDesc('efficiency')
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

        $this->calcGeneralStats();

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
        if (!$row = ($this->data->cahsbox[$pin] ?? null))
            return $this;

        $this->row->cahsbox += $row['sum'];

        foreach (($row['dates'] ?? []) as $date => $data) {

            if (empty($this->row->dates[$date]))
                $this->row->dates[$date] = $this->userRowDateTemplate($date);

            $this->row->dates[$date]->cahsbox += $data;
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
     * Подсчет общей статистики
     * 
     * @return $this
     */
    public function calcGeneralStats()
    {
        foreach ($this->data->stats as &$callcenter) {

            foreach ($callcenter->sectors as &$sector) {

                $callcenter->comings += $sector->comings;
                $callcenter->requestsAll += $sector->requestsAll;
                $callcenter->requests += $sector->requests;

                if ($callcenter->requests > 0)
                    $callcenter->efficiency = round(($callcenter->comings / $callcenter->requests) * 100, 2);

                $sector->dates = $this->setDatesArray($sector->dates);

                foreach ($sector->dates as &$row) {

                    if ($row->requests > 0)
                        $row->efficiency = round(($row->comings / $row->requests) * 100, 2);

                    if (empty($callcenter->dates[$row->date]))
                        $callcenter->dates[$row->date] = $this->userRowDateTemplate($row->date);

                    $callcenter->dates[$row->date]->comings += $row->comings;
                    $callcenter->dates[$row->date]->requestsAll += $row->requestsAll;
                    $callcenter->dates[$row->date]->requests += $row->requests;
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

        if (in_array($this->row->pin, $this->admins))
            return "blue";

        if ($this->dates->start >= "2021-07-01") {

            if ($this->row->efficiency >= 25)
                return "green";
            elseif ($this->row->efficiency >= 20)
                return "yellow";
        }

        return "red";
    }
}
