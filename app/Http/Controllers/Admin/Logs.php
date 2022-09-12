<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\UserDataFind;
use App\Models\Log;
use Illuminate\Http\Request;

class Logs extends Log
{
    /**
     * Атрибуты, которые будут преобразованы
     *
     * @var array
     */
    protected $casts = [
        'to_crypt' => 'boolean',
        'row_data' => 'array',
        'request_data' => 'array',
    ];

    /**
     * Вывод настроек для страницы логирования
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        Log::select('table_name', 'database_name')
            ->distinct()
            ->get()
            ->each(function ($row) use (&$tables) {

                $tables[$row->database_name][] = $row->table_name;
            });

        return response()->json([
            'tables' => $tables ?? [],
        ]);
    }

    /**
     * Вывод одной строки лога
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        $row = parent::select('*')
            ->when((bool) $request->id and $request->step == "next", function ($query) use ($request) {
                $query->where('id', '>', $request->id)->orderBy('id');
            })
            ->when((bool) $request->id and $request->step == "back", function ($query) use ($request) {
                $query->where('id', '<', $request->id)->orderBy('id', "DESC");
            })
            ->when(!(bool) $request->id, function ($query) {
                $query->orderBy('id', "DESC");
            })
            ->when((bool) $request->db, function ($query) use ($request) {
                $query->where('database_name', $request->db);
            })
            ->when((bool) $request->table, function ($query) use ($request) {
                $query->where('table_name', $request->table);
            })
            ->first();

        if ($row) {

            $row->to_crypt_access = false;

            if ($row->to_crypt and $request->user()->can('show_crypt_data')) {
                $row->to_crypt_access = true;
                $row->row_data = Controller::decrypt($row->row_data);
                $row->request_data = Controller::decrypt($row->request_data);
            }

            if ($row->user_id)
                $row->user = (new UserDataFind($row->user_id))();

            $row->count = parent::where([
                ['database_name', $row->database_name],
                ['table_name', $row->table_name],
                ['row_id', $row->row_id],
            ])->count();
        }

        return response()->json([
            'row' => $row ?? new static,
            'model' => $row->row_data ?? [],
            'request' => $row->request_data ?? [],
        ]);
    }
}
