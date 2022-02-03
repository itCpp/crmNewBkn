<?php

namespace App\Http\Controllers\Queues;

use App\Events\QueueUpdateRow;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Queues\QueueProcessings;
use App\Http\Controllers\Users\Users;
use App\Models\IpInfo;
use App\Models\RequestsQueue;
use App\Models\Company\BlockHost;
use App\Models\Company\StatVisit;
use Illuminate\Http\Request;

class Queues extends Controller
{
    /** Количество строк на страницу @var int */
    const LIMIT = 50;

    /**
     * Вывод очереди
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getQueuesManualPaginate(Request $request)
    {
        $done = (bool) $request->done;

        if ($done)
            return $this->getQueues($request);

        $request->page = $request->page ?: 1;
        $offset = self::LIMIT * $request->page - self::LIMIT;

        $query = (new RequestsQueue)
            ->when($request->last === null, function ($query) {
                $query->where('done_type', null);
            })
            ->when($request->last !== null, function ($query) use ($request, $offset) {
                $query->where('id', '>=', $request->first)
                    ->where(function ($query) {
                        $query->where('done_pin', '!=', "AUTO")
                            ->orWhere('done_pin', null);
                    })
                    ->offset($offset);
            })
            ->limit(self::LIMIT);

        $total = (new RequestsQueue)
            ->when($request->first === null, function ($query) {
                $query->where('done_type', null);
            })
            ->when($request->first !== null, function ($query) use ($request) {
                $query->where('id', '>=', $request->first)
                    ->where(function ($query) {
                        $query->where('done_pin', '!=', "AUTO")
                            ->orWhere('done_pin', null);
                    });
            })
            ->count();

        $queues = $query->get()
            ->map(function ($row) {
                return $this->modifyRow($row);
            })
            ->toArray();

        return response()->json([
            'queues' => $queues ?? [],
            'current' => $request->page,
            'next' => $request->page + 1,
            'total' => $total,
            'pages' => ceil($total / self::LIMIT),
        ]);
    }

    /**
     * Вывод очереди
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getQueues(Request $request)
    {
        $done = (bool) $request->done;

        $data = (new RequestsQueue)
            ->when($done, function ($query) {
                $query->where('done_type', '!=', null)
                    ->orderBy('done_at', 'DESC');
            })
            ->when(!$done, function ($query) {
                $query->where('done_type', null)
                    ->orderBy('id');
            })
            ->paginate(self::LIMIT);

        foreach ($data as $row) {
            $queues[] = $this->modifyRow($row);
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
     * Преобразование строки очереди
     * 
     * @param \App\Models\RequestsQueue $row
     * @return array
     */
    public function modifyRow($row)
    {
        $show_phone = request()->user()->can('clients_show_phone');
        $request_data = (array) parent::decrypt($row->request_data);

        $row->key = (request()->page ?: 1) . "_" . $row->id;

        if (isset($request_data['phone']))
            $request_data['phone'] = parent::displayPhoneNumber($request_data['phone'], $show_phone);

        $row->phone = $request_data['phone'] ?? null;
        $row->show_phone = $show_phone;
        $row->name = $request_data['client_name'] ?? null;
        $row->comment = $request_data['comment'] ?? null;

        $row->request_data = $request_data;

        $row->hostname = $this->getHostName($row->ip);
        $row->ipInfo = $this->getIpInfo($row->ip);
        $row->doneInfo = $this->getDropInfo($row);
        $row->ipBlocked = $this->getBlockIpInfo($row->ip);

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

        $queue = $this->modifyRow($row);

        $append_row = RequestsQueue::where('done_type', null)
            ->offset(self::LIMIT - 1)
            ->first();

        if ($append_row)
            $append = $this->modifyRow($append_row);

        broadcast(new QueueUpdateRow($queue))->toOthers();

        return response()->json([
            'queue' => $queue,
            'added' => $added ?? null,
            'append' => $append ?? null,
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

        if (!empty($this->users[$row->done_pin]))
            return $this->users[$row->done_pin];

        $name = Users::findUserPin($row->done_pin)->name_fio ?? "Завершено автоматически";

        return $this->users[$row->done_pin] = $name;
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

        $row = StatVisit::where('ip', $ip)
            ->where('host', '!=', null)
            ->orderBy('id', "DESC")
            ->first();

        if ($row)
            $name = $row->host;

        // gethostbyaddr($ip);

        return $this->hostnames[$ip] = $name ?? null;
    }

    /**
     * Информация об IP
     * 
     * @param string $ip
     * @return array
     */
    public function getIpInfo($ip)
    {
        if (!empty($this->ip_info[$ip]))
            return $this->ip_info[$ip];

        return $this->ip_info[$ip] = IpInfo::where('ip', $ip)->first();
    }

    /**
     * Информация о блокировки IP
     * 
     * @param string $ip
     * @return boolean
     */
    public function getBlockIpInfo($ip)
    {
        if (!empty($this->ip_block[$ip]))
            return $this->ip_block[$ip];

        $block = BlockHost::where('host', $ip)->first();

        return $this->ip_block[$ip] = ($block->block ?? null) == 1;
    }
}
