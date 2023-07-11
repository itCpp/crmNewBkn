<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    /**
     * Экспорт заявок
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return 
     */
    public function export(Request $request)
    {
        if (!$request->start || !$request->stop) {
            abort(400, "Необходимо указать дату начала и окончания периода выборки");
        }

        $start = now()->parse($request->start)->format("Y-m-d");
        $stop = now()->parse($request->stop)->format("Y-m-d");

        $path = "requests/export/"
            . now()->format("YmdHis")
            . "-exportleads-"
            . now()->create($start)->format("Ymd")
            . "-"
            . now()->create($stop)->format("Ymd")
            . ".txt";

        $param = [
            '--start' => $start,
            '--stop' => $stop,
            '--filename' => $path,
        ];

        foreach (['city', 'theme'] as $key) {

            if ($request->$key) {

                $$key = is_array($request->$key)
                    ? collect($request->$key)->implode(",")
                    : $request->$key;
    
                $param['--' . $key] = $$key;
            }
        }

        Artisan::call('requests:export', $param);

        $storage = Storage::disk('local');

        if (!$storage->exists($path)) {
            abort(400, "Сгенерироваанный файл не найден");
        }

        return response()->file($storage->path($path));
    }
}
