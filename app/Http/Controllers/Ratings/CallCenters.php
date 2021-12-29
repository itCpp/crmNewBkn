<?php

namespace App\Http\Controllers\Ratings;

use App\Exceptions\ExceptionsJsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Dates;
use Illuminate\Http\Request;

class CallCenters extends Controller
{
    use CallCenters\CallCenterResult,
        Data\Comings,
        Data\Requests,
        Data\Users;

    /**
     * Данные на вывод
     * 
     * @var object
     */
    public $data;

    /**
     * Данные запроса
     * 
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Флаг вывода полных данных
     * 
     * @var bool
     */
    protected $full_data;

    /**
     * Создание экземпляра объекта
     * 
     * @param \Illuminate\Http\Request
     * @param bool $full_data Флаг вывод полных данных рейтинга
     * @return void
     */
    public function __construct(Request $request, $full_data = false)
    {
        $this->request = $request;

        $this->data = (object) [
            'users' => [], # Данные расчитанного рейтинга
            'pins' => [], # Список всех сотрудников, найденных при расчете рейтинга
            'comings' => [], # Данные по приходам
            'requests' => [], # Подсчет заявок
        ];

        $this->dates = new Dates($request->start, $request->stop);

        $this->full_data = $request->user()->can('rating_callcenter_full_data') ?: $full_data;
    }

    /**
     * Вывод основного рейтинга колл-центров
     * 
     * @return object
     */
    public function get()
    {
        $this->getComings()
            ->getRequests()
            ->findUsers()
            ->getResult();

        if ($this->full_data)
            return $this->data;

        return (object) [
            'users' => $this->data->users,
        ];
    }

    /**
     * Вывод рейтинга для страницы с личными данными
     * 
     * @param string|int $pin
     * @return array|null
     */
    public function getMyRow($pin)
    {
        $this->dates = new Dates(type: "periodNow");

        $users = $this->get()->users ?? [];

        foreach ($users as $user) {
            if ($user->pin === $pin)
                return $user;
        }

        return null;
    }
}
