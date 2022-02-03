<?php

namespace App\Http\Controllers\Testing;

use App\Http\Controllers\Controller;
use App\Models\TestingProcess;
use Illuminate\Http\Request;

class MyTests extends Controller
{
    /**
     * Выводит список пройденных тестов
     * 
     * @param \Illumiante\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function mytests(Request $request)
    {
        $old_pin = $request->user()->old_pin;

        $query = TestingProcess::where(function ($query) use ($request, $old_pin) {
            $query->wherePin($request->user()->pin)
                ->when((bool) $old_pin, function ($query) use ($old_pin) {
                    $query->orWhere('pin_old', $old_pin);
                });
        })
            ->orderBy('id', "DESC");

        return response()->json([
            'count_done' => $query->where('done_at', '!=', null)->count(),
            'count_new' => $this->countTestings($request->user()->pin, $old_pin),
            'rows' => $query->limit(30)->get()->toArray(),
        ]);
    }

    /**
     * Количество незавершенных тестирований
     * 
     * @param int $pin
     * @param null|string $old_pin
     * @return int
     */
    public static function countTestings($pin, $old_pin = null)
    {
        return TestingProcess::where('done_at', null)
            ->where(function ($query) use ($pin, $old_pin) {
                $query->wherePin($pin)
                    ->when((bool) $old_pin, function ($query) use ($old_pin) {
                        $query->orWhere('pin_old', $old_pin);
                    });
            })
            ->count();
    }
}
