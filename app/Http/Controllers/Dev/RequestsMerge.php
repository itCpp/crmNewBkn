<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Requests\AddRequest;
use App\Http\Controllers\Requests\RequestChange;
use App\Http\Controllers\Users\UsersMerge;
use App\Models\CrmMka\CrmNewIncomingQuery;
use App\Models\RequestsClient;
use App\Models\RequestsComment;
use App\Models\RequestsRow;
use App\Models\User;
use App\Models\CrmMka\CrmRequest;
use App\Models\CrmMka\CrmRequestsRemark;
use App\Models\CrmMka\CrmRequestsSbComment;
use App\Models\IncomingQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class RequestsMerge extends Controller
{
    /**
     * Идентификатор последней проверки
     * 
     * @var int
     */
    protected $lastId;

    /**
     * Количество заявок в старой базе
     * 
     * @var int
     */
    public $count;

    /**
     * Максимальный идентификатор заявки
     * 
     * @var int
     */
    public $max;

    /**
     * Экземпляр объекта создания заявки
     * 
     * @var \App\Http\Controllers\Requests\AddRequest
     */
    protected $add;

    /**
     * @var string
     */
    protected $sourceToSourceCrypt = "eyJpdiI6IkJpVUZEcC9NODJPNjQrNXpGRHZxc2c9PSIsInZhbHVlIjoiVHdrd29SOWIzUjltYTAwdzRrTTdva0tnUGltL0o2QkNJc1ZFYm5neDRQZUJ4b1Z4b1FGQ2JBbG8xWFNCbkMvQXB2QzJPUjhIb2Y0cGNRUGthU25semxaMS9sNHFyL3A1Y1E1ZkNpcUIvbW5oejd1YlFFU093S2Z5MVRZZ1I0ampOcDdnQVBETlFpOWU3akxvUDdmSjVMcGtpb3ZPNWVueU0vdEtZdkxkV0NaNFhCK3lLQSs3YlAxUVRnT3hUSW5qSmRydEJmZURWYTBDVHA3OUdQTzM3WjY5alNicmtNY1VjcFNNYm1hRHlOQnlRc3l0M0toaElPUEs1TUdyeHlFOGl2NVhVem5OaWxBOHkrOUNpdVQ3RXFjRFpWdXF2aGF2cWI0ZXFSYmU1ckxEbU1PdWFBK3JzVVN5cWcrU0ZkNzRRbWRIdzZWWHJlZWIvZFlzVmtLM2xZWW15YXlDNjVYa3hqRjdzUTlSNW8vOGxZU1l0b21NUlRpV2VnQVIxdWhBT3liY1VpQWg4U3RGcDJQY21kZkZmdW5tdzE4Njg0MXEzeXhQM1g0c1VZcy91WDMzUWhXa25WV2FLUzJLcEVZdXVpRFVBRG1Sclo3V1p3UkhGY1RWcmJTK2IxdEYxMU1DNlpBTjhQSk80OXBKOVRRT1FaaGRkWjJOK1VzSUk4blFKcHlNZXpvd05oSmlqTW5EWEZvZlBwY3BucC83WU1qdllwK1hmSVIrRHdjZlFjZUFDM0xCVDRQZnBKbkU0VitjVThTczBzdThJSzFnV2VSOHZWeUZrYjc2UGpFbjFUV2ttNGFCVTdOTlZRd2pSaVFtR2RmUlFlQStxOXdFdUVleUJPTnVKQXRLcytFWC9BK0F6b3lHb1U4T2ZCVjN0clhDc3RaQ0luUkZKZkt5aGlIbUJWQWlEUlBGZGhXRGRwV1lOUXBVUFhWb1UwUHNjQWJMcjBJQTNzL2o0TGgwR0h6OXZPUWdaSnZRSTNZNXQvQWVNdUhPOTV6bER5clFjT1d2MW56NmUvME42M0ZIUDNrY2ljSCtFdHBGamNDVU9TWWRyc1N5Z2dYcWkyc29sMmpGckkvUFZ0Qk1CWUxoa0lEb2MzVUFwWnFFUC85NUpZaVI1NzZ0disxU0lSNFVWalhZZTY4QUhvcEo2WmNHQ1hrM2hzRmkyYUY4cm9KdFpyUmtYZjlJczd1SjMycmtFOG0vcWhrZDkzNUVtTzBEd0VLNlg2S3RCUyt5M2w2azdLU2tuK2tHVTVCcDVZSGJycTVMVEZCckM3dG8xMUI0WEhCU0I4a0I3azdXbHFyUG9jRVpvck83NzRmQTcvUENNU1N0WllIU3lsWElJMnpSWWN5NEpTNEtDQUNOMUJTb2puNnd3NU9BbnQwTlpGRUI3WW56cHhJMXJyektSeUIzVmt6TkJFUzdybWNoRXdLTnlUWXNsbVdSMVhTOTJpRXQvQ21STzlWQUE4OHNhTUJPT1lwMmkwTDg0U2dVZENnUjUxRkV0cE5VMG0yamFzbU14djJyWExtSTl0Y3dobElpNTYwMXVDVDJDYTVrd1o3RXV1TURRazZ4SHZpa2MxNzdRL1B3YTkzZzlpRG4wN21rSnZoK0dQRkZyRHpWT1NJbkdsK2xBRk5LZUhEMVpxeWkrYVd5eStiWUZCUjUvY0U1K3ZjQlhUZGtML0dSNllKbm9Ydjd1WVcrZk9EeS9ZZFgyYWdJNGJlV1l2RGxxNllyRXJBVUlsbUlhYXdpS0FwU0pWVVBjQnVqNS9HN0dTQzQ1V0EvR21VRXNkWWc2UC84Q25wZnU5dnJpNWdrUUpYek9QY2xQT3ZKOEQyUjB0OGRiam1lNWZGUkNzSlcvNTYyb0xKMEQzSm14QUdBYW14V1RpYzE0bVJuZ2pRV2xMbkRmVDNiSFIzcG11RjRjcS8xeVJkNDMycVBFTDArOW0xVHZlNU83UTlDRGJEQWVuZFAxZ29IWXlDaTQ5VXZGSlVDazNyUmRhQTlHQ1FnZW5sVWVCa3hLSTdrd0MwdWtOMHcrWDdFdlZsK2gvWHF5bHhEaklidmFWVkVuUT09IiwibWFjIjoiMzYxZWQyZTkxNWU2N2MxZjg0MGZmMDc4MjA3NzlhNTk1ZWI5NjljZTRkNTNlZTEyNmM2ZDE0MTAyNzI0NGRiNCIsInRhZyI6IiJ9";

    /**
     * Массив сопоставления идентификаторов источников
     * 
     * @var array
     */
    public $sourceToSource = [];

    /**
     * @var string
     */
    protected $sourceResourceToRecourceCrypt = "eyJpdiI6IlJEdmtSYnFacHZtU1lvekR0RllESFE9PSIsInZhbHVlIjoiMmptUWpXU3Z2YzU3VENlb1AwdTE0R05qZGt3dnAvY2g5NTBaSy9BdUZDTm42QkxTdm1FU2Z1cHkrWk8wcnc4ZXkyZGErTWFLMjhRc3FaSlJDaUdRK3lFSWhoUHlMcWl1cEFrYnZYVzdLYkhyQ3Q1eXFzbzJWQTZWQmRLL1MvK2dCekZpdUg0NW5qYjAzZ1hwZEcxR3BsTnZFbWdxTU01TkY2QzV4UklBd0pRbHhvazlBUzJwdlBUSDYxY0FRN3ZiQTBJWTV1dVNmRXJndTZPUndYYTU2cThISVpEd0lqNnNkdm9BYnh0OWFpWXJqbFNLWU9SRDgvTFJUUUhMWTAxcFo1bE1GM09qOUN1cHNEQk5BY0x0MTgwbUI5bnVWYkF0ajFZc1RqcWVObXhHUk1OQkRsZGUzQ2Q3cUREVkNNM1NqZDRQNHBHNjVKWGM3bVNBd2JLUWdhdEkranZ4SWFMenlCUGV2ZVpKdXNuTHQ3aUY0Uzk4Ylp0OWZ1VFU1LzVmVGtRazVOeG9ZWEpISzl1bXZQMW54RFhjQTZQKzhsd1pDQWtLQmtJaWhGQnVERXhaTHF2ZnFFcWFtOUJZU25KSXNTWVJWMzZGNm9iMGx4VndFMUFrenhOREdzSERDdnFRVW1pbWJHdy9jUUFMSHA4ZFd1bERKY0xUT2k5OTJ5RlAxd1lWTy85a1JCak0vbzJHMEpnU2ViSnJ6QWdyRGZ1ek83NC9semswUlg3dUJ4dEd6eHMwa3FLZDZvSjZLRFBZaFluVlFvTmdYSkpSRGFkUlprb0FrL0tJdkZEK01jUUsrRldNWnRGTjhZZytsaG9pRTlLcEk5T2xkek93cy9HOW1FbWpKME90dHovaERSbDllNTFPSENpNkN6bmlaOVFWYUgrNmMxL1lvNFh2RDE3SWpIVUFKWEpzNzhqQzV4SHZKb25MRUxmOU1uKzhKN1lKcWhUTy93eUlJNFI3dmdUWS9FMGdyMkpDN2JnUnZLUDk3SjQ4ODZIMlc4K01rSWo1dElmZmNMRGdYUUorSmdDSU5UeFRqZEl3OHdLcFpWUEJwSGo0d2xZMDJrOFlIR1RXMmRGK1ZkTm00Uzc2REMyRVBhQjhZWEM3K01vWlc1S3lYMVpyRFZFaTdkbzl4SDFlSXAxUEMyY0ZjVXhPU3AzaEYyb1QzVGZpK0RaMGoyV0UvY2pPSWFZeWY3a05Qdm5UZ3ppSUNzb0JRb3FTakkvcHoxQi9hZTdaMHhSTUdrMUVIOE1KNlFTL2h3d3lEN2p0aGZWN1ZSdzQwTDF0ZFNiZHIyZzNzUEhsMmgvSmZWZ1BYRzBrSVJCbUMzbWtrT2E1dGkvaTQxM3hwMnpuOThhejZWQVRJdFIvTDFJSzNBNnVoSkEvYk56TjgvZWZDUWl3ZkliUVg5ZXRjY2JycmtMbGZ3WEtYbXN6WjBOYnJvVmhhVC9MQmtBNDlEOG9JUzBoV2ZoQ1hwVkNsVFJqc1AycUw4R2VyL1AyYkF6M1NpYXRTbnBiSTFUd2wzNHFYWGxqUXd3RUlOcXB1M1RSZXVtcHJzUkhrb21JVEsvUDFBUU5SUnpvNHg1SDcwcWtiL0tzdVZyYVhzYi9xVWFGY2p5empxRjcrNGZUaWppN2dBQUxnWEJhUlFOdUViTmNFaXNla1VjSDllZ0tJYmJxVDdSeHcwcXM2SWN0eUNPMmc1SlltY3VSUlVIYkthRjlWc1hQL3dEM3VLaWNYWlNXNUZoS1pMSWNUUjR3QTV3ZUxMc1BZZzEycE45bmJWMWdoWVYvelhzeEJRU0Q5cEhyaFMvRnN0b2ZqK0hQQnVYcHRhTGdnSUtTYUVxUFY0L0o0UXFiVG4xY2dZVTZUczFSN05LMHBZcTk5NXhTVzFqZlNieldOL0k1WjRCdUxkSHNrdmlSVmozbkpUaGRwV3NiaGpyZGVWcHJNeXRDVDloOTJKS1NYUU4rS1ZXc3lkUXFSRlVIREVESC9Rd01FdGpQVnFQNlErQ1k2YXp6WTBtVTNQVXkzNjhuYkdpSitEaitGTTQ3TGVxdi9YVVl1Y1JUVU00QzFmR3M4RGwwTVJ1bFBBUFZlMnZ2Sk8rczVIb21lRTJGUlNrVFc5am1qcmtYMXU1R0pJSnRFNGNqS1c0THN4cEViNUpQcUpYYXUza2FCVDR3Mk9scEhZS2c5UE5CcmMzelEvR0RXdS8vN1ZlSmZidmxXcE5uNjlzWFNqUGppTndmaXoyazlGbmdtZ01pWnkyZ1VPOUpHZ3pucnFKNEhTYlhBRGJVb05oWnBtQ1NLWVB5RFN5eld2L3NjRDMyQjZBSE5OeGJ1N1VrMzcwcFI5NlNSRVJXak05RkE3MXF1VWprQ0RVSHU4d08xcVdrd2Q1Q0M3WVp3Z2pGNDZyOC8yeVdlTU5mUW5WNXZtWnRlNWo4MG4weTQwRGdwMldTRnlTNmZ0SmlzUkZjdDBtRXh0UVh2Q0pkNFdSSVBOdGlUbWVZYXI4aTk3cTNKNkwrU0lxbEVaQ2I4QXdJVzJJNDJyZldhWEgxVnlJakpOR2xrMkIwMnFhMHhJdlA5WVNER2hjTzRCbWIydzBrN21HY1NRUzhjMVY3RUlKdWVaS1A5NW5BMng3cHU0ZkJaN1NlUE9XQStWSXU2YS9lTmhxd3lhU3ZYQjhoWHgrV0pTaUNKUlppQVcrWjNuUks2UzlqYnJtZkZLM0g4QVUvNDFmZTFwNXVobWtRaVFTeDhQV0NuMXpBbHRZeTBrWmdQelU1YlNVU3NBL3hmR3hUekpMWUpsYnNSVTJ3ZDVYNnN5Q3Q0TUI2WGFVNktaZGJrRTZaSG1MeUU0T0h5SytjOENmL05RRXc0L0ZSZmVCNzc1aGY1N2NDTWYvT1c0bDFGdjJQWi96NWNQcW93RVY1OFJUVWhaSEIrMU9RbjI5VlJ1T2oveG1sQlBnNlBDTEdtamxkUHlUclhJMS9ZcDM3c1lJRytUWmppRzF1MmVKdXU1bWk0Qlc4Tk4rbFM0QWVIL2M2QXdTRms0ZVV3Y1liZi9mWXlaNGc0UWxHWUMzWVlFU0Y0Ukd2K1pnbVpaZHQzMnhneXZHdXR5OSt5YWtaczdkSEVCM3BaMjBlM0dRWGZPbFp6QTBwWll0dWdVWnMxNi9DQXFuc0VTNHdBbnpXVWpYRGo1TE5mdFRKQXRWclpkdVdXT0JUVXpkR0IyYnZxNEtnR3RBK3pGQVNMU2Q5VU9Eb1ZGUGE5SEdQbG5aMDZSYnNSTnc1alNoU3dPK014N3N0ak1ZS0VYTXNubTFwTDlJaUJmaUVFUTZhOCtXVnpiSlQ4cDlyIiwibWFjIjoiZjZjOGE1NWQ3MGM5NWE4NDlkNjg5ODY2MThhZTRhMTI2ODVhMTkwYzhlZWY3YzkwMDViZDQ0OGJhNGJlN2Y4MCIsInRhZyI6IiJ9";

    /**
     * Сопостовление ресурсов источника
     * 
     * @var array
     */
    public $sourceResourceToRecource = [];

    /**
     * Соотношение статуса старой заявки к идентификтору нового статуса
     * 
     * @var array
     */
    public $stateToStatusId = [
        'bk' => 5,
        'brak' => 6,
        'color-promo' => 12,
        'nedozvon' => 1,
        'neobr' => null,
        'online' => 10,
        'online-doc' => 11,
        'podtverjden' => 4,
        'prihod' => 7,
        'promo' => 9,
        'shipping' => 14,
        'sliv' => 8,
        'sozvon' => 2,
        'vtorich' => 13,
        'zapis' => 3,
    ];

    /**
     * Проверяемые сотрудники
     * 
     * @var array
     */
    protected $users = [];

    /**
     * Проверенные персональне номера сотрудников
     * 
     * @var array
     */
    protected $new_pins = [];

    /**
     * Объвление нового экзкмпляра
     * 
     * @return void
     */
    public function __construct()
    {
        $this->sourceToSource = decrypt($this->sourceToSourceCrypt);
        $this->sourceResourceToRecource = decrypt($this->sourceResourceToRecourceCrypt);

        $this->lastId = 0;
        $this->count = CrmRequest::count();
        $this->max = CrmRequest::max('id');

        $request = new Request;
        $this->add = new AddRequest($request);

        $this->usersMerge = new UsersMerge;
    }

    /**
     * Метод поиска строки
     * 
     * @return false|CrmRequest
     */
    public function getRow()
    {
        if (!$row = CrmRequest::where('id', '>', $this->lastId)->first())
            return false;

        $this->lastId = $row->id;

        return $row;
    }

    /**
     * Обработка заявки
     * 
     * @return false|CrmRequest
     */
    public function step()
    {
        if (!$row = $this->getRow())
            return false;

        // Проверка наличия заявки в новой БД для пропуска
        if ($check = RequestsRow::find($row->id))
            return $check;

        $data = (object) [];

        $data->row = $row->toArray(); # Данные старой заявки
        $data->phones = $this->getPhones($row); # Номера телефонов клиента

        // Поиск и/или создание клиентов
        $data->clients = $this->chenckOrCreteClients($data->phones);

        $new = new RequestsRow;

        $new->id = $row->id;
        $new->query_type = $this->getQueryType($row);
        $new->callcenter_sector = $this->getSectorId($row);
        $new->pin = $this->getNewPin($row->pin);
        $new->source_id = $this->getSourceId($row);
        $new->sourse_resource = $this->getResourceId($row);
        $new->client_name = $row->name != "" ? $row->name : null;
        $new->theme = $row->theme != "" ? $row->theme : null;
        $new->region = ($row->region != "" and $row->region != "Неизвестно") ? $row->region : null;
        $new->check_moscow = $new->region ? RequestChange::checkRegion($new->region) : null;
        $new->comment = $row->comment != "" ? $row->comment : null;
        $new->comment_urist = $row->uristComment != "" ? $row->uristComment : null;
        $new->comment_first = $row->first_comment != "" ? $row->first_comment : null;
        $new->status_id = $this->getStatusId($row);
        $new->address = $row->address ?: null;
        $new->uplift = $row->vtorCall == "vtorCall" ? 1 : 0;

        $new->event_at = $this->getEventAt($row);
        $new->created_at = $this->getCreatedAt($row);
        $new->uplift_at = $this->getUpliftAt($row, $new);
        $new->deleted_at = $this->getDeletedAt($row, $new);
        $new->updated_at = $this->getUpdatedAt($new);

        if ($new->uplift == 1) {

            /** Обнуление подъема со статусом */
            if ($new->status_id)
                $new->uplift = 0;
            /** Обнуление заявок с необарботанным статусом для определенных источников */
            else if (!$new->status_id and in_array($new->source_id, [3, 4, 5, 6, 17]))
                $new->uplift = 0;
        }

        $new->save();

        // Привязка клиента к заявке
        foreach ($data->clients as $client) {
            $client->requests()->attach($new->id);
        }

        // Поиск и добавление комментариев по заявке
        $data->comments = $this->getAndCreateAllComments($new->id);

        // Перенос истории обращений
        // $this->findAndRequestQueries($new);

        return $new;
    }

    /**
     * Поиск номеров телефона в старой заявке
     * 
     * @param CrmRequest $row
     * @return array
     */
    public function getPhones($row)
    {
        if ($phone = $this->checkPhone($row->phone, 1))
            $phones[] = $phone;

        $seconds = explode("|", $row->secondPhone);

        if (is_array($seconds)) {
            foreach ($seconds as $second) {
                if ($phone = $this->checkPhone($second, 1))
                    $phones[] = $phone;
            }
        }

        return array_unique($phones ?? []);
    }

    /**
     * Проверка и создание клиентов
     * 
     * @param array $phones
     * @return array
     */
    public function chenckOrCreteClients($phones)
    {
        foreach ($phones as $phone) {

            $hash = $this->add->getHashPhone($phone);

            $clients[] = RequestsClient::firstOrCreate(
                ['hash' => $hash],
                ['phone' => Crypt::encryptString($phone)]
            );
        }

        return $clients ?? [];
    }

    /**
     * Определение типа заявки
     * 
     * @param CrmRequest
     * @return string
     */
    public function getQueryType($row)
    {
        return $row->typeReq == "Звонок" ? "call" : "text";
    }

    /**
     * Определение сектора
     * 
     * @param CrmRequest
     * @return null|int
     */
    public function getSectorId($row)
    {
        $sector = $row->{'call-center'};

        if ($sector === "" or $sector === null)
            return null;

        $sectors = [
            1 => 1,
            2 => 1,
            3 => 2,
            4 => 2,
            5 => 2,
            6 => 2,
            7 => 3,
        ];

        return $sectors[(int) $sector] ?? null;
    }

    /**
     * Определение источника старой заявки
     * 
     * @param CrmRequest
     * @return null|int
     */
    public function getSourceId($row)
    {
        foreach ($this->sourceToSource as $source) {
            if ($row->type == $source[1])
                return $source[0];
        }

        return null;
    }

    /**
     * Определение ресурса источника заявки
     * 
     * @param CrmRequest
     * @return null|int
     */
    public function getResourceId($row)
    {
        foreach ($this->sourceResourceToRecource as $resource) {
            if ($row->typeSiteLink === $resource[1] or $this->checkPhone($row->myPhone, 3) === $resource[1])
                return $resource[0];
        }

        return null;
    }

    /**
     * Поиск нового пина сотрудника
     * 
     * @param string $pin
     * @return int|string
     */
    public function getNewPin($pin)
    {
        $key = md5($pin);

        if (!empty($this->new_pins[$key]))
            return $this->new_pins[$key];

        if (!$pin)
            return $this->new_pins[$key] = null;

        if (!$user = User::where('old_pin', $pin)->first())
            return $this->getFiredAndCreateNewUser($pin, $key);

        return $this->new_pins[$key] = $user->pin;
    }

    /**
     * Создание уволенного сотрудника
     * 
     * @param string $pin
     * @param null|string $key
     * @return int
     */
    public function getFiredAndCreateNewUser($pin, $key = null)
    {
        $key = $key ?: md5($pin);

        if (!$user = $this->usersMerge->createFiredUser($pin))
            return $this->new_pins[$key] = $pin;

        return $this->new_pins[$key] = $user->pin;
    }

    /**
     * Определение идентификатора статуса заявки
     * По умолчнию будет бракованная заявка, чтобы обнулить её при следующем поступлении
     * 
     * @param CrmRequest|CrmNewRequestsState $row
     * @return null|int
     */
    public function getStatusId($row)
    {
        return $this->stateToStatusId[$row->state] ?? 6;
    }

    /**
     * Определение времени события
     * 
     * @param CrmRequest $row
     * @return null|string
     */
    public function getEventAt($row)
    {
        if (!$row->rdate or !$row->time)
            return null;

        $date = trim($row->rdate . " " . $row->time);
        $time = strtotime($date);

        if (!$time or $time > 1735678800 or $time < 1420070400)
            return null;

        return $date;
    }

    /**
     * Дата создания заявки
     * 
     * @param CrmRequest $row
     * @return null|string
     */
    public function getCreatedAt($row)
    {
        $date = $row->staticDate;

        if (!$date or $date == "")
            $date = $row->date;

        if (!$date or $date == "")
            return null;

        return trim($date . " " . $row->staticTime);
    }

    /**
     * Определение времени подъема заявки
     * 
     * @param CrmRequest $row
     * @param RequestsRow $new
     * @return null|string
     */
    public function getUpliftAt($row, $new)
    {
        $time = null;

        if ($row->timeSort and $row->timeSort != "")
            $time = $row->timeSort;

        if (!$time and $new->created_at)
            $time = strtotime($new->created_at);

        return $time ? date("Y-m-d H:i:s", $time) : null;
    }

    /**
     * Дата и время удаления заявки
     * 
     * @param CrmRequest $row
     * @param RequestsRow $new
     * @return null|string
     */
    public function getDeletedAt($row, $new)
    {
        if ($row->del != "hide" and $row->noView != 1)
            return null;

        if ($date = $this->getUpdatedAt($new))
            return $date;

        return now();
    }

    /**
     * Дата и время обновления
     * 
     * @param RequestsRow $new
     * @return null|string
     */
    public function getUpdatedAt($new)
    {
        $date = null;

        if (!$date or $new->created_at > $date)
            $date = $new->created_at;

        if (!$date or $new->event_at > $date)
            $date = $new->event_at;

        if (!$date or $new->uplift_at > $date)
            $date = $new->uplift_at;

        if (!$date or $new->uplift_at > $date)
            $date = $new->uplift_at;

        return $date;
    }

    /**
     * Поиск и добавление комментариев по заявке
     * 
     * @param int $id Идентификтаор заявки
     * @return int Количество комментариев
     */
    public function getAndCreateAllComments($id)
    {
        $count = 0;

        // Комментарии СБ
        foreach (CrmRequestsSbComment::where('request_id', $id)->get() as $row) {
            RequestsComment::create([
                'request_id' => $id,
                'type_comment' => "sb",
                'created_pin' => $this->getCommentAuthor($row->pin),
                'comment' => $row->comment,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);

            $count++;
        }

        // Заметки
        foreach (CrmRequestsRemark::where('idReq', $id)->get() as $row) {
            RequestsComment::create([
                'request_id' => $id,
                'type_comment' => "comment",
                'created_pin' => $this->getCommentAuthor($row->pin),
                'comment' => $row->remark,
                'created_at' => $row->create_at,
                'updated_at' => $row->create_at,
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Поиск сотрудника по старому пину
     * 
     * @param int|string $pin
     * @return int
     */
    public function getCommentAuthor($pin)
    {
        if (!empty($this->users[$pin]))
            return $this->users[$pin]->pin ?? $pin;

        if (!User::where('old_pin', $pin)->first())
            $this->users[$pin] = $this->getFiredAndCreateNewUser($pin);

        return $this->users[$pin]->pin ?? $pin;
    }

    /**
     * Поиск и перенос истории обращений
     * 
     * @param \App\Models\RequestsRow $row
     * @return null
     */
    public function findAndRequestQueries($row)
    {
        CrmNewIncomingQuery::where('id_request', $row->id)
            ->orderBy('created_at')
            ->get()
            ->each(function ($item) {

                $query_data = is_array($item->request) ? $item->request : [];

                if (isset($query_data['number']))
                    $query_data['phone'] = $query_data['number'];

                $hash_phone = isset($query_data['phone'])
                    ? $this->hashPhone($query_data['phone'])
                    : null;

                if (!$hash_phone and $item->phone) {
                    $query_data['phone'] = $item->phone;
                    $hash_phone = $this->hashPhone($item->phone);
                }

                $client_id = optional(RequestsClient::where('hash', $hash_phone)->first())->id;

                $hash_phone_resource = isset($query_data['myPhone'])
                    ? $this->hashPhone($query_data['myPhone'])
                    : null;

                if (!$hash_phone_resource and $item->myPhone) {
                    $query_data['myPhone'] = $item->myPhone;
                    $hash_phone_resource = $this->hashPhone($item->myPhone);
                }

                $ad_source = isset($query_data['utm_source'])
                    ? $query_data['utm_source'] : null;

                if (isset($query_data['phone']))
                    $query_data['phone'] = $this->encrypt($query_data['phone']);

                if (isset($query_data['number']))
                    $query_data['number'] = $this->encrypt($query_data['number']);

                if (isset($query_data['ip'])) {
                    $item->ip = $query_data['ip'];
                    $query_data['ip_from_item'] = $item->ip;
                }

                if ($item->typeReq == "Звонок")
                    $type = "call";
                else if ($item->typeReq == "Текст")
                    $type = "text";

                $query_data['hash_gate'] = $item->hash_gate;
                $query_data['xml_cdr_uuid'] = $item->xml_cdr_uuid;
                $query_data['cdr_date'] = $item->cdr_date;

                $request_data = [
                    'id' => $item->id_request,
                ];

                foreach ($this->sourceToSource as $source) {
                    if ($source[1] == $item->type) {
                        $request_data['source_id'] = $source[0];
                        break;
                    }
                }

                $phone = $this->checkPhone($item->myPhone, 3);
                $site = isset($query_data['site']) ? $query_data['site'] : null;

                foreach ($this->sourceResourceToRecource as $source) {
                    if ($source[1] == $phone or $source[1] == $site) {
                        $request_data['sourse_resource'] = $source[0];
                        break;
                    }
                }

                $create = [
                    'query_data' => $query_data,
                    'client_id' => $client_id,
                    'request_id' => $item->id_request,
                    'ad_source' => $ad_source,
                    'type' => $type ?? null,
                    'hash_phone' => $hash_phone,
                    'hash_phone_resource' => $hash_phone_resource,
                    'request_data' => $request_data,
                    'ip' => $item->ip,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];

                IncomingQuery::create($create);
            });

        return null;
    }
}
