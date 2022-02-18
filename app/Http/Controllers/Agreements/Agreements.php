<?php

namespace App\Http\Controllers\Agreements;

use App\Http\Controllers\Controller;
use App\Models\Base\CrmAgreement;
use App\Models\Base\CrmAgreementComment;
use App\Models\Base\CrmKassa;
use App\Models\Base\Office;
use App\Models\Base\Personal;
use App\Models\RequestsRow;
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
        $row->oristFioArr = $this->getLawyerList($row->oristFio);

        // Добавление иконки офиса
        $row->icon = $this->getOfficeIcon($row->company);

        // Определение цвета строки
        $row->color = $this->getRowColor($row->colStatus);

        // Идентификаторы заявок
        $row->unicIdClient = (int) $row->unicIdClient;

        // Список номеров телефона клиента
        $row->phones = $this->getPhones($row);

        $row->avans = (int) $row->avans;
        $row->summa = (int) $row->summa;
        $row->ostatok = (int) $row->ostatok;
        $row->predstavRashod = $this->getPredstavRashodSumm($row->nomerDogovora);

        // Объект расходов
        $row->predstavRashodJson = json_decode($row->predstavRashodJson) ?: [];

        $this->getComments($row);

        return collect($row->toArray())->only([
            'FullNameClient',
            'avans',
            'colStatus',
            'collDate',
            'color',
            'coming_date',
            'coming_time',
            'comment',
            'commentOkk',
            'comments',
            'date',
            'icon',
            'id',
            'nomerDogovora',
            'odIspolnitel',
            'oristFioArr',
            'ostatok',
            'phones',
            'predmetDogovora',
            'predstavRashod',
            'predstavRashodJson',
            'rashodPoDogovory',
            'status',
            'summa',
            'tematika',
            'unicIdClient',
        ])->all();
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

    /**
     * Подсчет суммы представильских расходов
     * 
     * @param array $numbers Номера договоров
     * @return array
     */
    public function getPredstavRashodSumm($number)
    {
        return CrmKassa::selectRaw('sum(predstavRashod) as sum')
            ->where('nomerDogovora', $number)
            ->where('predstavRashod', '!=', "")
            ->sum('predstavRashod');
    }

    /**
     * Определение цвета строки
     * 
     * @param string|int $status
     * @return string
     */
    public function getRowColor($status)
    {
        if ($status == 0)
            return "status-neobr";

        if ($status == 1)
            return "status-good";

        if ($status == 2)
            return "status-check";

        if ($status == 3)
            return "nstatus-bad";

        if ($status == 4)
            return "status-nocall";

        return "status-no";
    }

    /**
     * Определение списка ЮПП
     * 
     * @param string $upp
     * @return array
     */
    public function getLawyerList($upp)
    {
        foreach (explode("/", $upp ?? "") as $row) {

            if (trim($row) == "")
                continue;

            $list[] = $this->getPersonalData(trim($row));
        }

        return $list ?? [];
    }

    /**
     * Поиск номеров телефона клинта
     * 
     * @param object $row
     * @return array
     */
    public function getPhones($row)
    {
        if ($row->unicIdClient)
            return $this->getPhonesFromRequest($row->unicIdClient);

        $phones = json_decode($row->phones, true) ?: [];
        $list = [];

        // Поиск номеров из таблицы клиента
        foreach ($phones as $phone) {

            $phone = $this->checkPhone($phone, 3);

            if ($phone and !in_array($phone, $list))
                $list[] = $phone;
        }

        // Поиск номеров из таблицы договора
        $phones = explode(" ", $row->phone);

        foreach ($phones as $phone) {

            $phone = $this->checkPhone($phone, 3);

            if ($phone and !in_array($phone, $list))
                $list[] = $phone;
        }

        $phones = [];

        foreach ($list ?? [] as $key => $phone) {
            $phones[] = $this->serializePhoneRow($phone, "+{$row->id}d{$key}");
        }

        return $phones;
    }

    /**
     * Номера телефона из заявки
     * 
     * @param int $id
     * @return array
     */
    public function getPhonesFromRequest($id)
    {
        if (!$row = RequestsRow::find($id))
            return [];

        foreach ($row->clients as $client) {

            $phone = $this->decrypt($client->phone);

            $phones[] = $this->serializePhoneRow($phone, "+{$id}s{$client->id}");
        }

        return $phones ?? [];
    }

    /**
     * Формирование строки с номером телефона
     * 
     * @param string $phone
     * @param string id
     * @return array
     */
    public static function serializePhoneRow($phone, $id)
    {
        $show = request()->user()->can('clients_show_phone');
        $type = $show ? parent::KEY_PHONE_SHOW : parent::KEY_PHONE_HIDDEN;

        return [
            'number' => $show ? parent::checkPhone($phone, 3) : $id,
            'phone' => parent::checkPhone($phone, $type),
        ];
    }

    /**
     * Вывод всех комментариев по договору
     * 
     * @param  \App\Models\Base\CrmAgreement  $row
     * @return \App\Models\Base\CrmAgreement
     */
    public function getComments(&$row)
    {
        $rows = CrmAgreementComment::where('del', 0)
            ->where('idAgreement', $row->id)
            ->whereIn('type', $this->comment_types)
            ->orderBy('date', 'DESC')
            ->get();

        $comments = []; // Все комментарии
        $predstavs = []; // Список всех представителей

        $odIspolnitel = null; // Текущий исполнитель
        $predmet = null; // Предмет договора

        /** Создание пустых контейнеров */
        foreach ($this->comment_types as $type) {
            $comments[$type] = [];
        }

        foreach ($rows as $comment) {

            if ($comment->type == "act")
                $comment->text = $this->getActData($comment->text);
            else
                $comment->text = htmlspecialchars_decode($comment->text);

            if ($comment->type == "predmetDogovora") {

                if (!$predmet)
                    $predmet = $comment->text;
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

        if ($predmet)
            $row->predmetDogovora = $predmet;

        return $row;
    }

    /**
     * Данные акта
     * 
     * @param string $data JSON строка
     * @return array|string
     * 
     * @todo Взять идентификтаоры из раздела договоров и добавить в метод
     */
    public function getActData($data)
    {
        if ($response = json_decode($data, true)) {

            if ($response['type'] == '0')
                $response['type'] = 'Акт';
            else if ($response['type'] == '1')
                $response['type'] = 'Промакт';
            else if ($response['type'] == '2')
                $response['type'] = 'Акт с объяснительной';
        }

        return $response ?: $data;
    }
}
