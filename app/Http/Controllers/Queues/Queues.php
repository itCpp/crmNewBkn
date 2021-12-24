<?php

namespace App\Http\Controllers\Queues;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Queues\QueueProcessings;
use App\Http\Controllers\Users\Users;
use App\Models\RequestsQueue;
use Illuminate\Http\Request;

class Queues extends Controller
{
    /**
     * Проверенные имена хостов определенных ip
     *  
     * @var array
     */
    public $hostnames = [];

    /**
     * Список сотрудников, принимавших решение по завершению запроса
     * 
     * @var array
     */
    public $users = [];

    /**
     * Вывод очереди
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getQueues(Request $request)
    {
        $show_phone = $request->user()->can('clients_show_phone');
        $done = (bool) $request->done;

        $data = RequestsQueue::where('done_type', $done ? '!=' : '=', null)
            ->orderBy(
                $done ? 'done_at' : 'id',
                $done ? "DESC" : "ASC"
            )
            ->paginate(30);

        foreach ($data as $row) {
            $queues[] = $this->modifyRow($row, $show_phone);
        }

        return response()->json([
            'queues' => $queues ?? [],
            'current' => $data->currentPage(),
            'next' => $data->currentPage() + 1,
            'total' => $data->total(),
            'pages' => $data->lastPage(),
        ]);
    }

    /**
     * Получение имени хоста
     * 
     * @param string $ip
     * @return string|null
     */
    public function getHostName($ip)
    {
        if (!empty($this->hostnames[$ip]))
            return $this->hostnames[$ip];

        return $this->hostnames[$ip] = gethostbyaddr($ip);
    }

    /**
     * Преобразование строки очереди
     * 
     * @param \App\Models\RequestsQueue $row
     * @param boolean $show_phone
     * @return array
     */
    public function modifyRow($row, $show_phone = false)
    {
        $request_data = (array) parent::decrypt($row->request_data);

        if (isset($request_data['phone']))
            $request_data['phone'] = parent::displayPhoneNumber($request_data['phone'], $show_phone);

        $row->phone = $request_data['phone'] ?? null;
        $row->name = $request_data['client_name'] ?? null;
        $row->comment = $request_data['comment'] ?? null;

        $row->request_data = $request_data;

        $row->hostname = $this->getHostName($row->ip);

        $row->doneInfo = $this->getDropInfo($row);

        return $row->toArray();
    }

    /**
     * Решение по очереди
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function done(Request $request)
    {
        if (!$row = RequestsQueue::find($request->create ?: $request->drop))
            return response()->json(['message' => "Очередь не найдена"], 400);

        if ($request->create) {
            $row->done_type = 1;
            $added = (new QueueProcessings($row))->add();
        } else if ($request->drop)
            $row->done_type = 2;

        $row->done_at = now();
        $row->done_pin = $request->user()->pin;

        $row->save();

        return response()->json([
            'queue' => $this->modifyRow($row, $request->user()->can('clients_show_phone')),
            'added' => $added ?? null,
        ]);
    }

    /**
     * Вывод информации о завершении запроса
     * 
     * @param \App\Models\RequestsQueue $row
     * @return null|string
     */
    public function getDropInfo(RequestsQueue $row)
    {
        if (!$row->done_type)
            return null;

        if (empty($this->users[$row->done_pin]))
            $this->users[$row->done_pin] = Users::findUserPin($row->done_pin);

        return $this->users[$row->done_pin]->name_fio ?? "Завершено автоматически";
    }
}
