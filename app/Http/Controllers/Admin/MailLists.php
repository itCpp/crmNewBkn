<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\UsersMailListJob;
use App\Models\UsersMailList;
use Illuminate\Http\Request;

class MailLists extends Controller
{
    /** Стандартный тип уведомления @var string */
    const TYPE = "info";

    /**
     * Вывод рассылок
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        $data = UsersMailList::orderBy('id', "DESC")->paginate(50);

        $rows = $data->map(function ($row) {
            return $this->serializeRow($row)->toArray();
        })->toArray();

        return response()->json([
            'rows' => $rows,
            'page' => $data->currentPage(),
            'pages' => $data->last(),
            'total' => $data->total(),
            'next' => $data->currentPage() + 1,
        ]);
    }

    /**
     * Формирование строки
     * 
     * @param  \App\Models\UsersMailList $row
     * @return \App\Models\UsersMailList
     */
    public function serializeRow(UsersMailList $row)
    {
        if (!$row->done_at) {
            $row->fail = $row->created_at < now()->subMinutes(5);
        }

        if (!$row->icon) {
            $row->icon = match($row->type) {
                'error' => "remove",
                'success' => "checkmark",
                'warning' => "warning circle",
                'info' => "announcement",
                default => "announcement",
            };
        }

        return $row;
    }

    /**
     * Создание новой рассылки
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $row = UsersMailList::create([
            'title' => $request->title,
            'icon' => $request->icon,
            'type' => $request->type ?: self::TYPE,
            'message' => $request->message,
            'to_push' => $request->to_push,
            'to_notice' => $request->to_notice,
            'to_online' => $request->to_online,
            'to_telegram' => $request->to_telegram,
            'markdown' => $request->markdown,
            'author_pin' => optional($request->user())->pin,
        ]);

        UsersMailListJob::dispatch($row);

        return response()->json([
            'row' => $this->serializeRow($row),
        ]);
    }
}
