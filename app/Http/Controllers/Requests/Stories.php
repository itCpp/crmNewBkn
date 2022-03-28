<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Models\RequestsRow;
use App\Models\RequestsStory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class Stories extends Controller
{
    /**
     * Ключи, имеющие изменения
     * 
     * @var array
     */
    public $keys = [];

    /**
     * Предыдущие данные
     * 
     * @var array
     */
    public $last = null;

    /**
     * Ключи, которые не будут выводится в историю
     * 
     * @var array
     */
    protected $hidden = [
        'id',
        'old_story',
        'updated_at',
        'pivot',
    ];

    /**
     * Вывод истории изменения в заявке
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        $request->getRow = true;

        $row = Requests::getRow($request);

        if ($row instanceof JsonResponse)
            return $row;

        $rows = RequestsStory::whereRequestId($request->id)
            ->orderBy('created_at', 'ASC')
            ->get()
            ->map(function ($item) {
                return $this->serialize($item);
            })
            ->sortByDesc('sort')
            ->values()
            ->all();

        return response()->json([
            'row' => $row,
            'rows' => $rows,
            'count' => RequestsStory::whereRequestId($request->id)->count(),
        ]);
    }

    /**
     * Формирование строки истории
     * 
     * @param  \App\Models\RequestsStory $item
     * @return array
     */
    public function serialize(RequestsStory $item)
    {
        $row = new RequestsRow;
        $this->keys = [];

        if (is_array($item->row_data)) {

            foreach ($item->row_data as $key => $value) {

                if (in_array($key, $this->hidden))
                    continue;

                $row->$key = $value;
                $row_data[] = ['key' => $key, 'value' => $value];
            }
        }

        $item->row_data = $row_data ?? [];

        /** Определение измененных ключей */
        foreach ($row->toArray() as $key => $value) {
            if (($this->last[$key] ?? null) != $value) {
                $this->keys[] = $key;
            }
        }

        /** Первая строка записи */
        if ($this->last === null)
            $this->last = $row->toArray();

        /** Уникальный список измененных ключей */
        $item->keys = array_values(array_unique($this->keys));

        /** Идентификатор сортирвки */
        $item->sort = strtotime($item->created_at);

        /** Вывод данных о статусе */
        $item->status = Requests::getRequestRowStatusData($row);

        $this->last = $row->toArray();

        return $item->toArray();
    }
}
