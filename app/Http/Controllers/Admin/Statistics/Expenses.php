<?php

namespace App\Http\Controllers\Admin\Statistics;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpensesAccount;
use Illuminate\Http\Request;

class Expenses extends Controller
{
    /**
     * Вывод расходов
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        $count = Expense::select('date')->distinct()->count();

        return response()->json([
            'rows' => $rows ?? [],
            'total' => $count,
        ]);
    }

    /**
     * Данные для сохдания или редактирвоания строки расхода
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request)
    {
        $accounts = ExpensesAccount::orderBy('name')
            ->get()
            ->map(function ($row) {
                return [
                    'key' => $row->id,
                    'text' => $row->name,
                    'value' => $row->id,
                ];
            });

        return response()->json([
            'accounts' => $accounts,
        ]);
    }

    /**
     * Сохранение расхода
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        $request->validate([
            'account_id' => ["required", function ($attribute, $value, $fail) {
                if (is_integer($value) and !ExpensesAccount::find($value))
                    $fail("Аккаунт не найден");
                else if (is_string($value) and ExpensesAccount::where('name', $value)->count())
                    $fail("Аккаунт с таким именем уже существует");
            }],
            'date' => ["required", "date"],
            'sum' => ["required", "numeric", "min:0"],
            'requests' => ["required", "numeric", "min:0"],
        ]);

        if (is_string($request->input('account_id'))) {

            $account = ExpensesAccount::create([
                'name' => $request->account_id,
            ]);

            $this->logData($request, $account);

            $request->account_id = $account->id;
        }

        $row = Expense::find($request->id);

        if ($request->id and !$row)
            return response()->json(['message' => "Строка расхода не найдена или удалена"], 400);

        if (!$row)
            $row = new Expense();

        $row->account_id = $request->account_id;
        $row->date = $request->date;
        $row->requests = $request->requests;
        $row->sum = $request->sum;

        $row->save();

        $this->logData($request, $row);

        return response()->json([
            'row' => $row,
        ]);
    }
}
