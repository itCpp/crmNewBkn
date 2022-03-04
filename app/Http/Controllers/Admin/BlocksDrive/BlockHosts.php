<?php

namespace App\Http\Controllers\Admin\BlocksDrive;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BlockHosts extends Controller
{
    /**
     * Вывод заблокированных IP
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json([]);
    }
}
