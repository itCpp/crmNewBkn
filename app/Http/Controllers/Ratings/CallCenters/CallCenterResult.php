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
     * Подсчет данных основного рейтинга
     * 
     * @return $this
     */
    public function getResult()
    {
        foreach ($this->data->pins as $row) {
            $this->data->users[] = $this->getResultRow($row);
        }

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
            ->setRequests();

        return $this->row;
    }

    /**
     * Подсчет приходов
     * 
     * @return $this
     */
    public function setComings()
    {
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

            if (!isset($this->row->dates['date']))
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

        $this->row->requests += $requests['all'];
        $this->row->requestsAll += $requests['moscow'];

        foreach (($requests['dates'] ?? []) as $date => $data) {

            if (!isset($this->row->dates['date']))
                $this->row->dates[$date] = $this->userRowDateTemplate($date);

            $this->row->dates[$date]->requests += $data['all'];
            $this->row->dates[$date]->requestsAll += $data['moscow'];
        }

        return $this;
    }
}
