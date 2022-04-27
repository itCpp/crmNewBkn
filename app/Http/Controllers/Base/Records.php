<?php

namespace App\Http\Controllers\Base;

use App\Http\Controllers\Controller;
use App\Models\Base\Office as BaseOffice;
use App\Models\Office;
use App\Models\RequestsRow;
use Illuminate\Http\Request;

class Records extends Controller
{
    /**
     * Выводит записи на день
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        $statuses = $this->envExplode('STATISTICS_OPERATORS_STATUS_RECORD_ID');
        $checkeds = $this->envExplode('STATISTICS_OPERATORS_STATUS_RECORD_CHECK_ID');

        $request->date = $request->date ?: now()->format("Y-m-d");
        $date = now()->create($request->date)->startOfDay();

        $counter = [];

        $records = RequestsRow::where('event_at', '>=', $date)
            ->whereIn('status_id', $statuses)
            ->when((bool) $request->office, function ($query) use ($request) {

                $base = BaseOffice::find($request->office);

                if ($office = Office::where('base_id', $base->oldId ?? null)->first()) {
                    $query->where('address', $office->id);
                }
            })
            ->orderBy('event_at')
            ->get()
            ->map(function ($row) use ($checkeds, &$counter) {

                $response_row = [
                    'client_name' => $row->client_name,
                    'id' => $row->id,
                    'date' => $row->event_at,
                    'checked' => in_array($row->status_id, $checkeds),
                    'address' => (int) $row->address,
                    'comment' => $row->comment,
                    'comment_urist' => $row->comment_urist,
                    'pin' => $row->pin,
                    'theme' => $row->theme,
                ];

                $key = (int) $row->address;

                if (!isset($counter[$key]))
                    $counter[$key] = 0;

                $counter[$key]++;

                return array_merge(
                    $this->getOffice((int) $row->address),
                    $response_row
                );
            });

        foreach (($this->offices ?? []) as $addr => $row) {
            $row['records'] = $counter[$addr] ?? 0;
            $row['address'] = $addr;
            $offices[] = $row;
        }

        return response()->json([
            'records' => $records,
            'offices' => $offices ?? [],
        ]);
    }

    /**
     * Проверка офиса
     * 
     * @param  int $id
     * @return array|null
     */
    public function getOffice($id)
    {
        if (!empty($this->offices[$id]))
            return $this->offices[$id];

        $office = Office::find($id);
        $base = BaseOffice::where('oldId', $office->base_id ?? null)->first();

        return $this->offices[$id] = [
            'office_icon' => $base->icon ?? null,
            'office_name' => $base->name ?? null,
            'office_id' => $office->base_id ?? null,
        ];
    }
}
