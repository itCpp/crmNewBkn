<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Models\RequestsComment;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Контроллер обработки комментариев
 */
class Comments extends Controller
{

    /**
     * Выиды комментариев
     * 
     * @var array
     */
    public static $types = [
        'client' => "Комментарий клиента",
        'system' => "Системный комментарий",
        'comment' => "Комментарий оператора",
        'sb' => "Комментарий службы безопасности",
    ];

    /**
     * Вывод комментариев по заявке
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Support\Collection
     */
    public static function getComments(Request $request)
    {

        $comments = collect([]);
        $pins = [];

        RequestsComment::where('request_id', $request->id)
            ->orderBy('created_at', "DESC")
            ->chunk(25, function ($rows) use (&$comments, &$pins) {
                foreach ($rows as $row) {

                    $comments[] = (object) $row->toArray();

                    if ($row->created_pin and !in_array($row->created_pin, $pins))
                        $pins[] = $row->created_pin;

                }
            });

        $users = [];
        foreach (User::whereIn('pin', $pins)->get() as $user) {
            $users[$user->pin] = $request->user()->createNameFull($user->surname, $user->name, $user->patronymic);
        }

        foreach ($comments as &$row) {
            $row->created_fio = $users[$row->created_pin] ?? null;
        }

        return $comments;
    }
}
