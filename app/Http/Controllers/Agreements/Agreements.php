<?php

namespace App\Http\Controllers\Agreements;

use App\Http\Controllers\Controller;
use App\Models\Base\CrmAgreement;
use App\Models\Base\CrmAgreementComment;
use App\Models\Base\Office;
use App\Models\Base\Personal;
use Illuminate\Http\Request;

class Agreements extends Controller
{
    use RowsQuery;

    /**
     * Список иконок офиса
     * 
     * @var array<string, string>
     */
    protected $offices = [];

    /**
     * Список данных сотрудников
     * 
     * @var array<int, \App\Models\Base\Personal>
     */
    protected $personals = [];

    /**
     * Типы комментариев для извлечения
     * 
     * @var array
     */
    protected $comment_types = [
        'act',
        'predmetDogovora',
        'odIspolnitel',
        'comment',
        'commentOKK',
        'epodComments',
        'nachPredComment',
        'sytRazgovora',
        'uppComment',
        'clientComment'
    ];

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
        ]);
    }

    /**
     * Сбор данных строки
     * 
     * @param \App\Models\Base\CrmAgreement $row
     * @return array
     */
    public function serialize(CrmAgreement $row)
    {
        // Список персональных номеров ЮПП
        foreach (explode("/", $row->oristFio ?? "") as $oristFio) {

            if (trim($oristFio) == "")
                continue;

            $oristFioArr[] = trim($oristFio);
        }

        $row->oristFioArr = $oristFioArr ?: [];

        // Объект расходов
        $row->predstavRashodJson = json_decode($row->predstavRashodJson) ?: [];

        // Добавление иконки офиса
        $row->icon = $this->getOfficeIcon($row->company);

        // Определение цвета строки
        $row->color = 'status-no';
        switch ($row->colStatus) {
            case 0:
                $row->color = 'status-neobr';
                break;
            case 1:
                $row->color = 'status-good';
                break;
            case 2:
                $row->color = 'status-check';
                break;
            case 3:
                $row->color = 'nstatus-bad';
                break;
            case 4:
                $row->color = 'status-nocall';
                break;
        }

        // Идентификаторы заявок
        $row->unicIdClient = (int) $row->unicIdClient;

        // Номера телефонов клиента
        $phones = json_decode($row->phones, true) ?: [];
        $phoneslist = [];

        // Поиск номеров из таблицы клиента
        foreach ($phones as $phone) {

            $phone = $this->checkPhone($phone, 3);

            if ($phone and !in_array($phone, $phoneslist))
                $phoneslist[] = $phone;
        }

        // Поиск номеров из таблицы договора
        $phones = explode(" ", $row->phone);

        foreach ($phones as $phone) {

            $phone = $this->checkPhone($phone, 3);

            if ($phone and !in_array($phone, $phoneslist))
                $phoneslist[] = $phone;
        }

        // Список номеров клиента
        $row->phones = $phoneslist;

        // Получение всех данных по договору
        $comments_rows = CrmAgreementComment::where('del', 0)
            ->where('idAgreement', $row->id)
            ->whereIn('type', $this->comment_types)
            ->orderBy('date', 'DESC')
            ->get();

        $comments = []; // Дополнительные данные
        $predstavs = []; // Писок представителей
        $odIspolnitel = null; // Текущий исполнитель

        foreach ($this->comment_types as $type) {
            $comments[$type] = [];
        }

        foreach ($comments_rows as $comment) {

            // Обработка данных акта
            if ($comment->type == "act") {

                if ($comment->text = json_decode($comment->text)) {

                    switch ($comment->text->type) {
                        case '0':
                            $comment->text->type = 'Акт';
                            break;
                        case '1':
                            $comment->text->type = 'Промакт';
                            break;
                        case '2':
                            $comment->text->type = 'Акт с объяснительной';
                            break;
                    }
                }
            }

            // Добавление представителей
            if ($comment->type == "odIspolnitel") {

                $comment->textPin = $this->getPersonalData($comment->text);

                if (!$odIspolnitel)
                    $odIspolnitel = $comment->textPin;

                $predstavs[] = $comment->textPin;
            }

            // Запись данных
            $comments[$comment->type][] = (object) [
                'id' => $comment->id,
                'created_at' => $comment->date,
                'text' => $comment->text,
                'textPin' => $comment->textPin,
                'author' => $this->getPersonalData($comment->pin),
            ];
        }

        $row->comments = $comments;

        $row->odIspolnitel = $odIspolnitel;

        return $row->toArray();
    }

    /**
     * Поиск икноки офиса
     * 
     * @param string $id
     * @return null|string
     */
    public function getOfficeIcon($id)
    {
        $key = md5($id);

        if (!empty($this->office[$key]))
            return $this->office[$key];

        $office = Office::where('oldId', $id)->first();

        return $this->office[$key] = ($office->icon ?? null);
    }

    /**
     * Поиск икноки офиса
     * 
     * @param string $id
     * @return null|string
     */
    public function getPersonalData($pin)
    {
        $pin = (int) $pin;

        if (!empty($this->personals[$pin]))
            return $this->personals[$pin];

        $personal = Personal::where('pin', $pin)->first();

        return $this->personals[$pin] = [
            'pin' => $pin,
            'fio' => $personal->fio ?? null,
            'doljnost' => $personal->doljnost ?? null,
        ];
    }
}
