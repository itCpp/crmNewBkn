<?php

namespace App\Http\Controllers\Ratings\CallCenters;

trait CallCenterItog
{
    /**
     * Подсчет данных основного рейтинга
     * 
     * @return $this
     */
    public function calculateData()
    {
        foreach ($this->data->pins as $row) {
            $this->data->users[] = $this->calculateDataRow($row);
        }

        return $this;
    }

    /**
     * Рейтинг одного сотрудника
     * 
     * @param object $row
     * @return object
     */
    public function calculateDataRow($row)
    {
        // Подсчет приходов
        if (isset($this->data->comings[$row->pin])) {
            $row->comings = $this->data->comings[$row->pin]['count'];
        }

        return $row;
    }
}
