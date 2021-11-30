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

        $this->dates = new Dates(
            $request->start ?? date("Y-m-d"),
            $request->stop ?? date("Y-m-d")
        );

        $this->full_data = $request->user()->can('rating_callcenter_full_data') ?: $full_data;
    }

    /**
     * Вывод основного рейтинга колл-центров
     * 
     * @return array
     */
    public function get()
    {
        $this->getComings()
            ->getRequests()
            ->findUsers()
            ->getResult();

        $response = $this->full_data
            ? $this->data
            : [
                'users' => $this->data->users,
            ];

        return $response;
    }
}
