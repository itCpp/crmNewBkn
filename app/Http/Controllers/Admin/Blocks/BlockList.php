<?php

namespace App\Http\Controllers\Admin\Blocks;

use App\Http\Controllers\Controller;
use App\Models\Company\BlockHost;
use Illuminate\Http\Request;

class BlockList extends Controller
{
    /**
     * Вывод заблокированных адресов
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public static function get(Request $request)
    {
        $rows = new BlockHost;

        if ($request->search)
            $rows = $rows->where('host', 'LIKE', "%{$request->search}%")->orderBy('host');
        else
            $rows = $rows->orderBy('id', "DESC");

        $rows = $rows->limit(35)->get();

        return [
            'rows' => $rows,
        ];
    }
}
