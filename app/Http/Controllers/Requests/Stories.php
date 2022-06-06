<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Offices\OfficesTrait;
use App\Models\CallcenterSector;
use App\Models\RequestsRow;
use App\Models\RequestsStory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class Stories extends Controller
{
    use OfficesTrait;

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
        'status_icon',
        'source_id',
        'sourse_resource',
        'last_phone',
        'deleted_at',
        'query_type',
    ];

    /**
     * Наименование колонок
     * 
     * @var array
     */
    protected $columns = [
        'pin' => "Оператор",
        'theme' => "Тематика",
        'region' => "Регион",
        'uplift' => "Новое обращение",
        'address' => "Адрес офиса",
        'comment' => "Суть обращения",
        'event_at' => "Время события",
        'status_id' => "Статус",
        'uplift_at' => "Время обращения",
        'created_at' => "Время создания",
        'client_name' => "Имя клиента",
        'check_moscow' => "Московский регион",
        'comment_first' => "Первичный комментарий",
        'comment_urist' => "Комментарий для юриста",
        'callcenter_sector' => "Сектор",
    ];

    /**
     * Колонки логического значения
     * 
     * @var array
     */
    protected $types_boolean = [
        'uplift',
        'check_moscow',
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
            'columns' => $this->columns,
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

                if (in_array($key, $this->types_boolean))
                    $value = (int) $value == 0 ? "Нет" : "Да";

                if ($key == "address")
                    $value = $this->getOfficeNameFromAddressId($value);
                else if ($key == "callcenter_sector")
                    $value = $this->getCallCenterSectorName($value);

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

    /**
     * Поиск наименования офиса
     * 
     * @param  null|int $id
     * @return null|string
     */
    public function getOfficeNameFromAddressId($id)
    {
        return $this->getOfficeName($id);
    }

    /**
     * Поиск наименования сектора
     * 
     * @param  null|int $id
     * @return null|string
     */
    public function getCallCenterSectorName($id)
    {
        if (!empty($this->call_center_sector_names[$id]))
            return $this->call_center_sector_names[$id];

        return $this->call_center_sector_names[$id] = CallcenterSector::find($id)->name ?? null;
    }
}
