<?php

namespace App\Http\Controllers\Fines;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\Notifications;
use App\Http\Controllers\Users\UserData;
use App\Models\Fine;
use App\Models\User;
use Illuminate\Http\Request;

class Fines extends Controller
{
    /**
     * Количество минут, в течение которых можно восстановить удаленный штраф
     * или удалить только что созданный штраф, для которых удаление запрещено
     * 
     * @var int
     */
    protected $pause = 15;

    /**
     * Вывод штрафов
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $data = Fine::withTrashed()
            ->when((bool) $request->search, function ($query) use ($request) {

                $pins = User::where('surname', 'LIKE', "%{$request->search}%")
                    ->orWhere('name', 'LIKE', "%{$request->search}%")
                    ->orWhere('patronymic', 'LIKE', "%{$request->search}%")
                    ->get()
                    ->map(function ($row) {
                        return $row->pin;
                    })
                    ->toArray();

                $query->where('user_pin', 'LIKE', "%{$request->search}%")
                    ->orWhereIn('user_pin', $pins);
            })
            ->orderBy('created_at', "DESC")
            ->paginate(40);

        foreach ($data as $row)
            $rows[] = $this->serialize($row);

        return response()->json([
            'rows' => $rows ?? [],
            'page' => $data->currentPage(),
            'pages' => $data->lastPage(),
            'total' => $data->total(),
        ]);
    }

    /**
     * Формирование строки со штрафом
     * 
     * @param  \App\Models\Fine $row
     * @return \App\Models\Fine
     */
    public function serialize(Fine $row)
    {
        $row->user_fio = $row->user_pin ? $this->getNameUser($row->user_pin) : null;
        $row->from_fio = $row->from_pin ? $this->getNameUser($row->from_pin) : null;

        /** Можно удалить штраф */
        $row->is_delete = (!$row->deleted_at and request()->user()->can('user_fines_delete'));

        /** Можно удалить недавно созданный штраф */
        if (!$row->deleted_at and !$row->is_delete and request()->user()->pin == $row->from_pin and $row->created_at > now()->subMinutes($this->pause)) {
            $row->is_delete = true;
        }

        /** Можно восстановить удаленный штраф */
        $row->is_restore = ($row->deleted_at and $row->deleted_at > now()->subMinutes($this->pause));

        return $row;
    }

    /**
     * ФИО сотрудника
     * 
     * @param  null|int $pin
     * @return string
     */
    public function getNameUser($pin)
    {
        if (!empty($this->users[$pin]))
            return $this->users[$pin];

        if ($user = User::wherePin($pin)->first())
            return $this->users[$pin] = (new UserData($user))->name_fio;

        return $this->users[$pin] = null;
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
        $request->validate([
            'fine' => 'required',
            'user_pin' => 'required',
        ]);

        $row = Fine::create([
            'user_pin' => $request->user_pin,
            'from_pin' => $request->user()->pin,
            'fine' => $request->fine,
            'comment' => $request->comment,
            'request_id' => $request->request_id,
            'fine_date' => $request->date ?: now(),
        ]);

        Notifications::createFineNotification($row);

        return response()->json(
            $this->serialize($row)->toArray()
        );
    }

    /**
     * Удаление штрафа
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        if (!$row = Fine::find($request->id))
            return response()->json(['message' => "Штраф не найден или уже был удален"], 400);

        $row->delete();

        $message = "Штраф на сумму " . $row->fine . " руб от " . date("d.m.Y", strtotime($row->fine_date)) . " г. был удален";

        Notifications::createFineNotification($row, $message);

        $this->logData($request, $row);

        return response()->json(
            $this->serialize($row)->toArray()
        );
    }

    /**
     * Восстановление удаленного штрафа
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore(Request $request)
    {
        if (!$row = Fine::withTrashed()->find($request->id))
            return response()->json(['message' => "Штраф не найден или уже был удален"], 400);

        if (!$row->deleted_at)
            return response()->json(['message' => "Штраф еще не был удален или уже его кто-то восстановил"], 400);

        if ($row->deleted_at < now()->subMinutes($this->pause))
            return response()->json(['message' => "Время восстановления прошло"], 400);

        $row->restore();

        $message = "Штраф на сумму " . $row->fine . " руб от " . date("d.m.Y", strtotime($row->fine_date)) . " г. восстановлен";

        Notifications::createFineNotification($row, $message);

        $this->logData($request, $row);

        return response()->json(
            $this->serialize($row)->toArray()
        );
    }
}
