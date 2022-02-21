<?php

namespace App\Http\Controllers\Agreements;

use App\Http\Controllers\Controller;
use App\Models\Base\CrmAgreement;
use App\Models\Base\CrmComing;
use App\Models\Base\CrmDogovorCollCenter;
use App\Models\Base\CrmDogovorCollCenterComment;
use App\Models\Base\CrmNotif;
use Illuminate\Http\Request;

class Agreements extends Controller
{
    use RowsQuery;

    /**
     * Вывод договоров
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $paginate = $this->getRows($request);

        $rows = $paginate->map(function ($row) {
            return $this->serialize($row);
        })->toArray();

        return response()->json([
            'rows' => $rows,
            'personals' => $this->personals,
            'current_page' => $paginate->currentPage(),
            'next_page' => $paginate->currentPage() + 1,
            'pages' => $paginate->lastPage(),
        ]);
    }

    /**
     * Вывод данных одного договора для окна редактирования
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        $row = CrmAgreement::select('crm_agreement.*', 'coll.status AS collStatus')
            ->where([
                ['crm_agreement.id', $request->id],
                ['crm_agreement.styles', 'NOT LIKE', '%ff0000%'],
                ['crm_agreement.nomerDogovora', '!=', '-'],
                ['crm_agreement.vidUslugi', 'NOT LIKE', '%Юр. консультация%'],
                ['crm_agreement.arhiv', 'NOT LIKE', '%Архив%'],
            ])
            ->leftjoin('crm_dogovor_coll_center as coll', function ($join) {
                $join->on('coll.nomerDogovora', '=', 'crm_agreement.nomerDogovora')
                    ->where('coll.last', 1);
            })
            ->first();

        if (!$row)
            return response()->json(['message' => "Договор не найден"], 400);

        return response()->json([
            'id' => $row->id,
            'row' => $this->serialize($row),
            'status' => (int) $row->collStatus,
        ]);
    }

    /**
     * Сохраняет статус и комментарий по договору
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        if (!$request->comment)
            return response()->json(['message' => "Укажите комментарий"], 400);

        if (!$row = CrmAgreement::find($request->id))
            return response()->json(['message' => "Договор не найден"], 400);

        $dateNotif = date("d.m.Y в H:i");
        $dateAgree = date("d.m.Y", strtotime($row->date));
        $pin = $request->user()->old_pin ?: $request->user()->pin;

        // Создание уведомления в БАЗЫ для ОКК
        if (in_array($request->status, [2, 3, 4])) {

            // Текст уведомления в БАЗЫ
            $message = "{$dateNotif} - PIN:{$pin}: ";
            $message .= "Договор №{$row->nomerDogovora} от {$dateAgree} г.; ";
            $message .= $request->comment;

            // Сохранение уведомления
            $notif = new CrmNotif;
            $notif->pin = $pin;
            $notif->seen = 0;
            $notif->nameNotif = "Коментарий колл-центра!";
            $notif->text = $message;
            $notif->date = date("Y-m-d H:i:s");
            $notif->paramSearch = $row->nomerDogovora;

            $notif->save();
        }

        $date = date("d.m.Y H:i:s");
        $message = "{$pin} {$date}\n{$request->comment}";

        $status = CrmDogovorCollCenter::where([
            ['nomerDogovora', $row->nomerDogovora],
            ['last', 1],
        ])->orderBy('id', 'DESC')->first();

        if (!$status) {

            $zapis = null;
            if ($coming = CrmComing::find($row->synchronizationId))
                $zapis = date("d.m.Yг.", strtotime($coming->date)) . " " . $coming->time;

            $status = new CrmDogovorCollCenter;
            $status->nomerDogovora = $row->nomerDogovora;
            $status->date = $date;
            $status->zapis = $zapis;
            $status->dateZakDog = $dateAgree . "г.";
            $status->last = 1;
        }

        $status->comment = $status->comment ? ($status->comment . "\n\n" . $message) : $message;
        $status->status = (int) $request->status;
        $status->pin = $pin;

        $status->save();

        $comment = new CrmDogovorCollCenterComment;
        $comment->id_row = $status->id;
        $comment->pin = $pin;
        $comment->comment = $request->comment;

        $comment->save();

        $row->collStatus = $status->status;

        return response()->json([
            'comment' => $comment,
            'status' => $status,
            'row' => $this->serialize($row),
            'notification' => $notif ?? null,
        ]);
    }
}
