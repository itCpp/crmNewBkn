<?php

namespace App\Http\Controllers\SecondCalls;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Requests\Requests;
use App\Http\Controllers\Requests\RequestStart;
use App\Models\IncomingSecondCall;
use App\Models\RequestsClient;
use App\Models\RequestsRow;
use App\Models\UsersViewPart;
use Illuminate\Http\Request;

class SecondCalls extends Controller
{
    /**
     * Найденные заявоки
     * 
     * @var array
     */
    protected $requests = [];

    /**
     * Найденные клиента
     * 
     * @var array
     */
    protected $clients = [];

    /**
     * Вывод звонков
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        $this->show_phone = $request->user()->can('clients_show_phone');

        $this->view = $request->page > 1
            ? (object) ['view_at' => $request->lastView]
            : UsersViewPart::getLastTime($request->user()->id, 'secondcalls');

        $view_at = $this->view->view_at; // Время последнего просмотра раздела

        $data = new IncomingSecondCall;

        $data = $data->orderBy('created_at', "DESC")->paginate(25);

        RequestStart::$permits = $request->user()->getListPermits(RequestStart::$permitsList);

        $rows = $data->map(function ($row) {
            return $this->getRowData($row);
        });

        if ($this->view instanceof UsersViewPart) {
            $this->view->view_at = date("Y-m-d H:i:s");
            $this->view->save();
        }

        return response()->json([
            'calls' => $rows,
            'pages' => $data->lastPage(),
            'next' => $data->currentPage() + 1,
            'total' => $data->total(),
            'view_at' => $view_at,
        ]);
    }

    /**
     * Преобразование данных одной строки
     * 
     * @param \App\Models\IncomingSecondCall $row
     * @return array
     */
    public function getRowData(IncomingSecondCall $row)
    {
        $row->client = RequestsClient::find($row->client_id);

        $phone = $this->decrypt($row->client->phone ?? null);
        $row->phone = $this->displayPhoneNumber($phone, $this->show_phone, 4);

        $row->requests = $this->getRequests($row->request_id);

        $names = [];

        foreach ($row->requests as $request) {
            if ($request->client_name ?? null) {
                $names[] = $request->client_name;
            }
        }

        $row->names = $names ?: null;

        $view_at = $this->view->view_at ?? null;
        $row->new_sms = (!$view_at or ($view_at and $this->view->view_at < $row->created_at));

        return $row->toArray();
    }

    /**
     * Поиск и вывод заявок клиента на момент звонка
     * 
     * @param array|null
     * @return array
     */
    public function getRequests($ids)
    {
        if (!$ids)
            return [];

        $requests = [];
        $notIn = [];

        foreach ($ids as $id) {
            if (isset($this->requests[$id])) {
                $requests[] = $this->requests[$id];
                $notIn[] = $id;
            }
        }

        $rows = RequestsRow::whereIn('id', $ids)->whereNotIn('id', $notIn)->get();

        foreach ($rows as $row) {
            $row = Requests::getRequestRow($row);
            $requests[] = $row;
            $this->requests[$row->id] = $row;
        }

        return $requests;
    }

    /**
     * Счетчик данных
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public static function getCounterNewSecondCalls(Request $request)
    {
        $view = UsersViewPart::getLastTime($request->user()->id, 'secondcalls');
        $counter = (new IncomingSecondCall)->where('call_date', date("Y-m-d"));

        $count = $counter->count();
        $update = 0;

        if ($view) {
            $view_at = $view->view_at ?? date("Y-m-d H:i:s");
            $update = $counter->where('created_at', '>', $view_at)->count();
        }

        return [
            'count' => $count,
            'update' => $update > 0,
        ];
    }
}
