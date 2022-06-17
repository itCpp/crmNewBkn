<?php

namespace App\Http\Controllers\Admin\Statistics;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpensesAccount;
use Illuminate\Http\Request;

class Expenses extends Controller
{
    /**
     * Количество дней для вывода на одну страницу
     * 
     * @var int
     */
    protected $days = 14;

    /**
     * Список ключей элементы которых должны быть исключены из строки расхода
     * 
     * @var array
     */
    protected $not_row = [
        'id',
        'created_at',
        'updated_at',
    ];

    /**
     * Вывод расходов
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        $count = Expense::select('date')->distinct()->count();

        $current_page = $request->page ?: 1;
        $last_page = (int) ceil($count / $this->days) ?: 1;

        $dates = $this->getDateList($current_page);

        $rows = [];

        Expense::select('expenses.*', 'expenses_accounts.name as account_name')
            ->join('expenses_accounts', 'expenses_accounts.id', '=', 'expenses.account_id')
            ->whereIn('date', $dates)
            ->orderBy('date', 'DESC')
            ->orderBy('account_name')
            ->get()
            ->each(function ($row) use (&$rows) {

                if (!isset($rows[$row->date])) {
                    $rows[$row->date] = [
                        'date' => $row->date,
                        'expenses' => [],
                    ];
                }

                $id = (int) $row->account_id;

                if (!isset($rows[$row->date]['expenses'][$id])) {
                    $rows[$row->date]['expenses'][$id] = collect($row->toArray())->except($this->not_row);
                } else {
                    $rows[$row->date]['expenses'][$id]['requests'] += $row->requests;
                    $rows[$row->date]['expenses'][$id]['sum'] += $row->sum;
                }
            });

        foreach ($rows as &$row) {
            $row['expenses'] = array_values($row['expenses']);
        }

        return response()->json([
            'rows' => array_values($rows),
            'page' => $current_page,
            'nextPage' => $current_page + 1,
            'lastPage' => $last_page,
            'total' => $count,
            'limit' => $this->days,
        ]);
    }

    /**
     * Выводит список дат для вывода
     * 
     * @param  int $page
     * @return array
     */
    public function getDateList($page)
    {
        return Expense::select('date')
            ->orderBy('date', 'DESC')
            ->distinct()
            ->offset($page * $this->days - $this->days)
            ->limit($this->days)
            ->get()
            ->map(function ($row) {
                return $row->date;
            })
            ->toArray();
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
            'row' => Expense::find($request->id),
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
        } else {
            $account = ExpensesAccount::find($request->account_id);
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
        $expense = $row->toArray();

        $row->account_name = $account->name ?? null;

        return response()->json([
            'expense' => $expense,
            'row' => $this->countAllDataFromExpenseRow($row),
        ]);
    }

    /**
     * Подсчет данных аккаунта по строке расхода
     * 
     * @param  \App\Models\Expense $row
     * @return array
     */
    public function countAllDataFromExpenseRow(Expense $row)
    {
        $requests = 0;
        $sum = 0;

        Expense::selectRaw('sum(requests) as requests, sum(sum) as sum')
            ->whereAccountId($row->account_id)
            ->where('date', $row->date)
            ->get()
            ->each(function ($row) use (&$requests, &$sum) {
                $requests += $row->requests;
                $sum += $row->sum;
            });

        $row->requests = $requests;
        $row->sum = $sum;

        return collect($row->toArray())->except($this->not_row);
    }

    /**
     * Список расходов по аккаунту за дату
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $rows = Expense::whereAccountId($request->account_id)
            ->where('date', $request->date)
            ->get();

        return response()->json([
            'rows' => $rows,
        ]);
    }
}
