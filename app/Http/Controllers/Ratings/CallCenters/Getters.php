<?php

namespace App\Http\Controllers\Ratings\CallCenters;

/**
 * ТРЕЙТ для определения различных ставок и процентов
 * 
 * @method getOneComingSumPay() Расчет суммы за приход
 * @method getAdminPercent() Расчет ставки для руководителя за один приход его сектора
 * @method getChiefPercent() Расчет ставки за приход руководителю колл-центра
 * @method getAdminCashboxMonthPercent() Расчет премии руководителям сектора в конце месяца
 * @method getChiefCashboxPeriodPercent() Проценты от кассы за период (2 недели)
 * @method getColor() Определение цвета блока на страницу рейтинга
 * @method getColorAdmin() Определяет цвет блока для руководителя или админа
 */
trait Getters
{
    /**
     * Расчет суммы за приход
     * 
     * @param int $comings
     * @return int
     */
    public function getOneComingSumPay($comings = 0)
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

            if ($comings >= 60)
                return 500;
            else if ($comings >= 45)
                return 400;
            else if ($comings >= 35)
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
     * Расчет ставки для руководителя за один приход его сектора
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
     * Расчет ставки за приход руководителю колл-центра
     * 
     * Олег Ариэлевич Иванов, [10.08.20 10:38]
     * Приходы:
     * <=700 - 30р./пр-д
     * >700-<=750 - 35р./пр-д
     * >700 - 40р./пр-д
     * Касса:
     * 5 млн. - 2500р.
     * 7.5 млн. - 5000р.
     * 10 млн. - 7500р.
     * 12.5 млн. - 10000р.
     * 
     * @param int $comings
     * @return int
     */
    public function getChiefPercent($comings = 0)
    {
        if ($comings > 750)
            return 40;
        elseif ($comings > 700)
            return 35;

        return 30;
    }

    /**
     * Расчет премии руководителям сектора в конце месяца
     * 
     * @param null|int $sum Сумма кассы
     * @return int
     */
    public function getAdminCashboxMonthPercent($sum = null)
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
     * Проценты от кассы за период (2 недели)
     * 
     * Lera, [01.09.21 11:41]
     * Касса за 2 недели:
     * 5 млн. - 4000р.
     * 5.5 млн. - 4500р.
     * 6 млн. - 5000р.
     * 6.5 млн. - 5500р.
     * 7 млн. - 6000р.
     * 7.5 млн. - 6500р.
     * 8 млн. - 7000р.
     * 8.5 млн. - 7500р.
     * 9 млн. - 8000р. 
     * 9,5 млн – 8500
     * 10 млн – 9000
     * 10,5 млн – 9500
     * 11 млн – 10000
     * 11,5 млн – 10500
     * 12 млн – 11000
     * 12,5 млн - 11500
     * 13 млн – 12000
     * 13,5 млн – 12500
     * 14 млн – 13000 и т.д
     * 
     * @param null|int $sum
     * @return int
     */
    public function getChiefCashboxPeriodPercent($sum = null)
    {
        $sum = $sum ?: ($this->cashbox->sum->period ?? 0);

        $sum = $sum / 1000000;
        $floor = floor($sum);
        $round = round($sum, 2);
        $difference = $round - $floor;

        if ($round > 5) {
            $percent = (($floor * 1000) - 1000) + ($difference >= 0.5 ? 500 : 0);
        }

        return $percent ?? 0;
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
