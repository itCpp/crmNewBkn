<?php

namespace App\Http\Controllers\Admin\Blocks;

use App\Http\Controllers\Admin\Databases;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AllStatistics extends Controller
{
    /**
     * Доступные подключения
     * 
     * @var array
     */
    protected $connections = [];

    /**
     * Создание экземпляра объекта
     * 
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;

        $this->connections = Databases::setConfigs();
    }

    /**
     * Вывод статистики по сайтам из индивидуальных баз
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    static function get(Request $request)
    {
        $allstatistics = new static($request);

        return response()->json(
            $allstatistics->getData($request),
        );
    }

    /**
     * Вывод данных статистики по сайтам из индивидуальных баз
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getData(Request $request)
    {
        return [
            'connections' => $this->connections,
        ];
    }
}
