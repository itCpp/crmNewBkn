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
                $users[$row->pin] = $row;

                return $row;
            });

        $this->data->pins = $users ?? [];
        $this->data->old_to_new = collect($old_to_new ?? []);

        return $this;
    }
}
