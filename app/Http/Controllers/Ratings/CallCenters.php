<?php

namespace App\Http\Controllers\Ratings;

use App\Exceptions\ExceptionsJsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Dates;
use Illuminate\Http\Request;

class CallCenters extends Controller
{
    use CallCenters\CallCenterResult,
        Data\Cashbox,
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

        $dates_type = null;

        if ($request->toPeriod)
            $dates_type = "periodNow";

        $this->dates = new Dates($request->start, $request->stop, $dates_type);

        $this->data = (object) [
            'users' => [], # Данные расчитанного рейтинга
            'pins' => [], # Список всех сотрудников, найденных при расчете рейтинга
            'comings' => [], # Данные по приходам
            'requests' => [], # Подсчет заявок
            'dates' => $this->dates,
            'stories' => (object) [], # История сотрудников
            'stats' => [], # Общая статистика
        ];

        $this->full_data = $request->user()->can('rating_callcenter_full_data') ?: $full_data;

        $this->positions_admin = $this->envExplode("RATING_ADMIN_POSITION_ID");
    }

    /**
     * Вызов несуществующих методов
     * 
     * @param string $name
     * @param array $arguments
     * @return $this
     */
    public function __call($name, $arguments)
    {
        return $this;
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
            ->getCashboxData()
            ->findAdmins()
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

    /**
     * Шаблон строки статистики
     * 
     * @param bool $forAll
     * @return object
     */
    public function getTemplateStatsRow($forAll = true)
    {
        $row = (object) [
            'cahsbox' => 0, # Касса
            'comings' => 0, # Количетство приходов
            'dates' => [], # Ежедневная статистика
            'efficiency' => 0, # КПД
            'requests' => 0, # Московские заявки
            'requestsAll' => 0, # Всего заявок
        ];

        if ($forAll)
            $row->sectors = [];

        return $row;
    }
}
