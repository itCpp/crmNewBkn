<?php

namespace App\Http\Controllers\Sms;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Gates\GateBase64;
use App\Models\Gate;
use App\Models\SmsMessage;
use App\Models\User;
use App\Models\UsersViewPart;
use Illuminate\Http\Request;

class Sms extends Controller
{
    /**
     * Список отрудников
     * 
     * @var \App\Http\Controllers\Gates\GateBase64
     */
    public $base64;

    /**
     * Список отрудников
     * 
     * @var array
     */
    public $users = [];

    /**
     * Информация по шлюзу
     * 
     * @var array
     */
    public $gates = [];

    /**
     * Создание экземпляра объекта
     * 
     * @return void
     */
    public function __construct()
    {
        $this->base64 = new GateBase64;
    }

    /**
     * Вывод всех сообщений
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        $this->show_phone = $request->user()->can('clients_show_phone');

        $this->view = $request->page > 1
            ? (object) ['view_at' => $request->lastView]
            : $this->getLastTime($request);

        $view_at = $this->view->view_at; // Время последнего просмотра раздела

        $data = SmsMessage::select('sms_messages.*')
            ->when(!$request->user()->can('sms_access_system'), function ($query) {
                $query->join('sms_request', 'sms_request.sms_id', '=', 'sms_messages.id');
            })
            ->when(in_array($request->direction, ["in", "out"]), function ($query) use ($request) {
                $query->where('direction', $request->direction);
            })
            ->orderBy('created_at', "DESC")
            ->orderBy('sent_at', "DESC")
            ->distinct()
            ->paginate(25);

        $rows = $data->map(function ($row) {
            return $this->getRowSms($row);
        });

        if ($this->view instanceof UsersViewPart) {
            $this->view->view_at = date("Y-m-d H:i:s");
            $this->view->save();
        }

        return response()->json([
            'messages' => $rows,
            'pages' => $data->lastPage(),
            'next' => $data->currentPage() + 1,
            'total' => $data->total(),
            'view_at' => $view_at,
        ]);
    }

    /**
     * Получение последнего времени просмотра раздела смс сообщений
     * 
     * @param \Illuminate\Http\Request $request
     * @return \App\Models\UsersViewPart
     */
    public function getLastTime(Request $request)
    {
        $view = UsersViewPart::whereUserId($request->user()->id)
            ->wherePartName('sms')
            ->first();

        if (!$view) {
            $view = new UsersViewPart;
            $view->part_name = "sms";
            $view->user_id = $request->user()->id;
        }

        return $view;
    }

    /**
     * Обработка строки сообщения
     * 
     * @param \App\Models\SmsMessage $row
     * @return array
     */
    public function getRowSms(SmsMessage $row)
    {
        $row->message = $this->base64->decode($row->message);
        $row->author = $this->findUserName($row->created_pin);

        $phone = $this->decrypt($row->phone);
        $row->phone = $this->displayPhoneNumber($phone, $this->show_phone ?? false, 4);

        $row->requests;

        $row->gateName = $this->getGateInfo($row->gate);

        $view_at = $this->view->view_at ?? null;
        $row->new_sms = (!$view_at or ($view_at and $this->view->view_at < $row->created_at));

        return $row->toArray();
    }

    /**
     * Поиск ФИО автора сообщения
     * 
     * @param string|int|null $pin
     * @return string|null
     */
    public function findUserName($pin)
    {
        if (!$pin)
            return null;

        if (!empty($this->users[$pin]))
            return $this->users[$pin];

        if (!$user = User::wherePin($pin)->first())
            return null;

        $name = trim(implode(" ", [
            $user->surname,
            $user->name,
            $user->patronymic,
        ]));

        return $this->users[$pin] = $name;
    }

    /**
     * Поиск информации о шлюзе
     * 
     * @param int|null $gate
     * @return string|null
     */
    public function getGateInfo($id)
    {
        if (!$id)
            return null;

        if (!empty($this->gates[$id]))
            return $this->gates[$id];

        if (!$gate = Gate::find($id))
            return null;

        return $this->gates[$id] = $gate->addr;
    }

    /**
     * Счетчик непрочитанных смс сообщений
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public static function getCounterNewSms(Request $request)
    {
        $counter = new SmsMessage;

        $view = UsersViewPart::whereUserId($request->user()->id)
            ->wherePartName('sms')
            ->first();

        if ($view) {
            $counter = $counter->where('created_at', '>', $view->view_at);
        }

        if (!$request->user()->can('sms_access_system')) {
            $counter = $counter->join('sms_request', 'sms_request.sms_id', '=', 'sms_messages.id');
        }

        return [
            'count' => $counter->count(),
        ];
    }
}
