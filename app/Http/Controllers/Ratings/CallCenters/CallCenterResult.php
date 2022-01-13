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

        $sorted = $users->sortByDesc('efficiency')
            ->each(function ($row) {
                $this->data->places[] = $row->efficiency;
            })
            ->sortByDesc('comings')
            ->sortBy(function ($user) {
                return array_search($user->color, $this->color_sorted);
            });

        $this->data->places = array_unique($this->data->places);

        $this->data->users = $sorted->values()
            ->map(function ($row) {

                $place = array_search($row->efficiency, $this->data->places);
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

        $this->setComings()
            ->setRequests()
            ->setCashbox()
            ->setDatesArray()
            ->setResult();

        return $this->row;
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

        $this->row->comings += $this->data->comings[$pin]['count'];

        foreach (($this->data->comings[$pin]['dates'] ?? []) as $date => $comings) {

            if (empty($this->row->dates[$date]))
                $this->row->dates[$date] = $this->userRowDateTemplate($date);

            $this->row->dates[$date]->comings += $comings;
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

        $this->row->requestsAll += $requests['all'];
        $this->row->requests += $requests['moscow'];

        foreach (($requests['dates'] ?? []) as $date => $data) {

            if (empty($this->row->dates[$date]))
                $this->row->dates[$date] = $this->userRowDateTemplate($date);

            $this->row->dates[$date]->requestsAll += $data['all'];
            $this->row->dates[$date]->requests += $data['moscow'];
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
     * @return $this
     */
    public function setDatesArray()
    {
        $dates = collect([]);

        foreach (($this->row->dates ?? []) as $data) {
            $dates->push($data);
        }

        $this->row->dates = $dates->sortBy('timestamp')
            ->values()
            ->all();

        return $this;
    }

    /**
     * Итоговый расчет бонусов, окладов и тд
     * 
     * @return $this
     */
    public function setResult()
    {
        $row = &$this->row;

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

        if ($this->row->position == "administration")
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
