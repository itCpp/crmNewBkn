<?php

namespace App\Http\Controllers\Fines;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Fines extends Controller
{
    /**
     * Вывод штрафов
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json();
    }

    /**
     * Вывод штрафа
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        return response()->json();
    }

    /**
     * Добавление нового штрафа
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        return response()->json();
    }

    /**
     * Удаление штрафа
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        return response()->json();
    }
}
