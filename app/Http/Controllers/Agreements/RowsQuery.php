<?php

namespace App\Http\Controllers\Agreements;

use App\Models\Base\CrmAgreement;
use App\Models\Base\CrmAgreementComment;
use App\Models\Base\CrmDogovorCollCenter;
use App\Models\Base\CrmDogovorCollCenterComment;
use App\Models\Base\CrmKassa;
use App\Models\Base\Office;
use App\Models\Base\Personal;
use App\Models\RequestsRow;
use App\Models\Saratov\Personal as SaratovPersonal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait RowsQuery
{
    /**
     * Список иконок офиса
     * 
     * @var array<string, string>
     */
    protected $offices = [];

    /**
     * Список данных сотрудников
     * 
     * @var array<int, array>
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
     * Наименование статусов
     * 
     * @var array<int, string>
     */
    protected $statuses = [
        0 => "Необработано",
        1 => "Клиент доволен",
        2 => "Отправлен на проверку",
        3 => "Негатив",
        4 => "Отказ от созвона",
    ];

    /**
     * Формирование запроса на вывод договоров
     * 
     * @param \Illuminate\Http\Request $request
     */
    public function getRows(Request $request)
    {
        return CrmAgreement::select(
            'crm_agreement.id',
            'crm_agreement.nomerDogovora',
            'crm_agreement.predmetDogovora',
            'crm_agreement.phone',
            'crm_agreement.status',
            'crm_agreement.date',
            'crm_agreement.tematika',
            'crm_agreement.predstavRashod',
            'crm_agreement.rashodPoDogovory',
            'crm_agreement.avans',
            'crm_agreement.summa',
            'crm_agreement.ostatok',
            'crm_agreement.doplata',
            'crm_agreement.company',
            'crm_agreement.oristFio',
            'crm_agreement.predstavRashodJson',
            'crm_agreement.FullNameClient',
            'c.date as coming_date',
            'c.time as coming_time',
            'c.unicIdClient',
            'crm_clients_unical.phone as phones',
            'crm_agreement.phone'
        )
            ->join('crm_coming as c', 'c.id', '=', 'crm_agreement.synchronizationId')
            ->leftjoin('crm_clients_unical', 'crm_clients_unical.id', '=', 'crm_agreement.uniclClientId')
            ->when(in_array($request->type, ['neobr', 'good', 'check', 'bad', 'nocall', 'search', 'all']), function ($query) use ($request) {
                $query = $this->setTypeQuery($query, $request);
            })
            ->where('crm_agreement.company', '!=', 'СПР')
            ->paginate(20);
    }

    /**
     * Применение типа договоров к запросу
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function setTypeQuery(Builder $query, Request $request)
    {
        if ($request->type == "return") {
            return $query->where('styles', 'LIKE', '%ffff01%')
                ->orderBy('crm_agreement.id', 'DESC')
                ->orderBy('crm_agreement.nomerDogovora', 'DESC');
        }

        $query = $query->select(
            'crm_agreement.id',
            'crm_agreement.nomerDogovora',
            'crm_agreement.predmetDogovora',
            'crm_agreement.phone',
            'crm_agreement.status',
            'crm_agreement.date',
            'crm_agreement.tematika',
            'crm_agreement.predstavRashod',
            'crm_agreement.rashodPoDogovory',
            'crm_agreement.avans',
            'crm_agreement.summa',
            'crm_agreement.ostatok',
            'crm_agreement.doplata',
            'crm_agreement.company',
            'crm_agreement.oristFio',
            'crm_agreement.predstavRashodJson',
            'crm_agreement.FullNameClient',
            'c.date as coming_date',
            'c.time as coming_time',
            'c.unicIdClient',
            'crm_clients_unical.phone as phones',
            'crm_agreement.phone',
            'coll.status as collStatus',
            'coll.comment',
            'coll.commentOkk',
            'coll.date as collDate'
        )
            ->leftjoin('crm_dogovor_coll_center as coll', function ($join) {
                $join->on('coll.nomerDogovora', '=', 'crm_agreement.nomerDogovora')
                    ->where('coll.last', 1);
            })
            ->where([
                ['crm_agreement.styles', 'NOT LIKE', '%ff0000%'],
                ['crm_agreement.nomerDogovora', '!=', '-'],
                ['crm_agreement.vidUslugi', 'NOT LIKE', '%Юр. консультация%'],
                ['crm_agreement.arhiv', 'NOT LIKE', '%Архив%'],
            ])
            ->when($request->type == "neobr", function ($query) {
                $query = $query->where(function ($query) {
                    $query->where('coll.status', 0)
                        ->orWhere('coll.status', "");
                });
            })
            ->when($request->type == "good", function ($query) {
                $query = $query->where('coll.status', 1);
            })
            ->when($request->type == "check", function ($query) {
                $query = $query->where('coll.status', 2);
            })
            ->when($request->type == "bad", function ($query) {
                $query = $query->where('coll.status', 3);
            })
            ->when($request->type == "nocall", function ($query) {
                $query = $query->where('coll.status', 4);
            })
            ->when(($request->type == "search" and (bool) $request->search), function ($query) use ($request) {

                $search = (object) $request->search;

                if ($search->number ?? null)
                    $query = $query->where('crm_agreement.nomerDogovora', $search->number);

                if ($search->name ?? null)
                    $query = $query->where('crm_agreement.FullNameClient', 'LIKE', "%{$search->name}%");

                if ($search->date ?? null)
                    $query = $query->where('crm_agreement.date', $search->date);

                if ($search->pin ?? null) {
                    $query = $query->where(function ($sql) use ($search) {
                        $sql->where('crm_agreement.oristFio', 'LIKE', "%{$search->pin}%")
                            ->orWhere('crm_agreement.odIspolnitel', 'LIKE', "%&gt;{$search->pin}%");
                    });
                }
            });

        if (in_array($request->type, ["all", "search"]) || $request->user()->can('clients_agreements_all')) {
            return $query->orderBy('crm_agreement.id', 'DESC')
                ->orderBy('crm_agreement.nomerDogovora', 'DESC');
        }

        return $query->where(function ($query) use ($request) {
            $query->where('c.collPin', $request->user()->pin)
                ->when((bool) $request->user()->old_pin, function ($query) use ($request) {
                    $query->orWhere('c.collPin', $request->user()->old_pin);
                });
        })->orderBy('coll.id');
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
        $row->color = $this->getRowColor($row->collStatus);

        $row->collStatusName = !empty($this->statuses[$row->collStatus])
            ? $this->statuses[$row->collStatus] : null;

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

        $row->collComments = $this->getCollComments($row);

        return collect($row->toArray())->only([
            'FullNameClient',
            'avans',
            'collComments',
            'collDate',
            'collStatus',
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

        if (!$personal = Personal::where('pin', $pin)->first())
            $personal = SaratovPersonal::where('pin', $pin)->first();

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
            return "status-bad";

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

        foreach (array_unique($list ?? []) as $key => $phone) {
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

    /**
     * Выводит комментарии колл-центра
     * 
     * @param object $row
     * @return array
     */
    public function getCollComments($row)
    {
        $statuses = CrmDogovorCollCenter::select('id')
            ->where('nomerDogovora', $row->nomerDogovora)
            ->get()
            ->map(function ($row) {
                return $row->id;
            })
            ->toArray();

        return CrmDogovorCollCenterComment::whereIn('id_row', $statuses)
            ->orderBy('id', 'DESC')
            ->get()
            ->map(function ($row) {
                $row->author = $this->getPersonalData($row->pin);
                return $row;
            })
            ->toArray();
    }
}
