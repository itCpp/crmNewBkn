<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Exception;

class RequestsMergeData extends Controller
{
    /**
     * Сопостовление идентификаторов источников
     * 
     * @var string
     */
    protected $sourceToSourceCrypt = "eyJpdiI6Im5oTFBCRFluQ3R1bFV0dmh1QlNWU3c9PSIsInZhbHVlIjoiTUc2MFpoNUJuSVhlN2w1OHcwYmR4Q0Q0aHlldzU4Z0V1TUF1NnJ2RFNSeEIrZHZsMjdERlh3VjhDbWZxSTVZWUl1NU14NWhsVTJhb1RuaFlFaTB4OUQwQlVzZ295TFR4dVhBWVNCSEJ2Y3ZhUCtIZlVqYUtHTjYxTEVaQkI4TTZaRHlUbWlOb1p6ZzQ4bzhzZUpmUUpoTFpwRzN3dWJxV1FmOTlqNEZpWHlPZnh2cWhQYzQxVldoaDZxSWFKK0kvQ0Z6Y2VIRlB2VXo0K2pXMWRWV2VPMXJ6ck01TXZVWUhsQXNNWVc1MVNGNlI4R0xNcjJoZHA5c3pVeHZaWmdpRk9BQUwzekoxZWR0UlgxaldkWHl1UTRSSUdXUXg5b2VBV2FOY0lkZk9RV2dzamRGRUpBM0tLZjlRME5tdXhrVUxabGd3cTA1cFdWVEFGTEFFY0hUUzZXaytlZm5rZk9KSjNMV1BGVDdBZFg0YmY3ZHdUT3BwMnlhMWdobzdEejJmdVlnVXRxMHZCOGJhOW41K2FZMlFUNWp5RkFqSGVuMW1MOHI1S3ZTWlFaUE5jVEhQUHlRbkkvelJKMXZURUk0eUNpU1E2ZjhNbjBhaUhGU0FIR1lVUkc2azRxaEs1NHVNWCtiMEZJb25objVvY2NuUFJsajZvaWJ5ejZJY29acnEzTVJmelJONnRaei9ReEIrNGF1cVczNlR6cUE5TGgrdmxJMU43dzI5OWpKalhSZ3VOZ1M4OHExUEN3Q3FVMGU5SFRuUkM1L2I3TXV6NWV3ZGdkL3F2TUNnaUo3NHYybGVmbHpLSjMrSVpGSmhIQjV0Vjh4bXZUSk5MUHlSY3YyMm5yTEd3UG11N0Z6aXk1Y2FEblhoaEV4bElHb3didk1Gd3Evb2paWEhMVVNOUE4yZmRSUzNqc0pTaExZdEFtUTZGYytjc1JHU3lqWjhBRm5JN0laSU9IbzIzTGlFcytHZ3p6V3NBS1hLU09DZGxxZUtIa2FDZGxnWFZ3M2ROMXdET2gweFZNMzlVaWVWNDZBc0E3bkNNVUZxdmpMVXdNZjUrMStFVjZNR21SaWJIcjFyYTBRcTU2NjJoY3pEalJuajR0dmhYWSswZkl3RkVyV3loODYrVHRUVHdSTUZzeTJsTW5JOGZtRjdpWkpPSjd5dDhRZGppQys2SFJia2JPQkJtdGZBQ3QxWWtxWVUxV1FIdm4vZm8rOXhlQ2JyWWp4L282N1RUa21LRWtheElWcHI5eGNOSVliZlBqeVlnMCtFM01KMVk5aXZOM096L2RHcEswemIrVWhUOEROT2hlbSsvUzNUTlRsYVEzbTJxQkc1M2hnQSsrY2RCTzlPMHJKT0F1ZlBTMVBnaDhMd2JzaDFidENDL0VOelRDOXVrN1FKMktwNFQzTUlJdFFtWHpNdzNrSHQ1MFp3WDIzT2EyOFM3SkhsKzlZbWNkc0I2NEpzZVhjWGNRZWRhSGRyaGxxaStRaE9BQ0Q4Ym1ORjBBYlJta0hTY1FYVWhtbFVCeFJLekJuL3RCeEpibE5nUitQZGFESko4M0JKeTZvb2ZpQXZOODM3S0dqNzJiKzJjM2NLejBKdCtpM0RlZmJFemxkZXJERk8xMjhDTUVCZEtaUUNGanlMcks1cyttK2V6aWRKZXJKSzFPVi9JMWFyalcyTUdpRU1sTWJBVEpDSy9ORlJDQ2VMbXdQNG9Dak00dk44T3pHUHRnZ0hvY21XT2huVjJVcDlsekRhVThrWWJMR2liMTI2cGs1M2VpYUlodVhxNnJDQm5DTExFejV4czhHT0xObEs3OHRISWU2Ull0aXM1a3RKTXRldWg3NkMvb0h4dkIwZHpkTXpqTktXRjRYeEdjcUdQK1JoM3dadEJHbHloSDQxNWVwVzFmSEdCM1ZIVXRBWXBMUkVxZTQ1N3hTZFc1TDhuSWJBSUtxSkJLbUdIaHZRdG9ZV0hQL25tYlpJc0pxdTRtenFLeEJEdHhmbFl2UzVPNzhzVmRCSGpQTDlJZWdtc1ZIMmhuVmgvMzhwNHo1VFV6c2QySFhSbUk2eURrZ0FyWHFPK0hSQy9XNTk2amsyMGNvWTZzVjVsV1l4VFUzeWo1M1Jpa2hEeGgvd2t0QVk2Um5RTXIyaW9XT2J4c1RUK0tkUkdsb1RFeXpUdXBEZ1kzUVB6NDhBbWdUc2p6Ty9xTDhkOXVxSytDVDIiLCJtYWMiOiI4MDRlNTlhMDNiNWMwNzdjMzQ3Mzg1NWVjODA1NGQxMzRiNjgyMzM2MTEyNTMyMjllNGQ4ZWYxOTFlZDAyNTM0IiwidGFnIjoiIn0=";

    /**
     * Сопостовление ресурсов источника
     * 
     * @var string
     */
    protected $sourceResourceToRecourceCrypt = "eyJpdiI6InRqbDV3elU3UVZUZFVncU4yZlZwbEE9PSIsInZhbHVlIjoiZmZrMmQxaXc5M2lMTi82YndJK0VDendEYkNxY0NFRzVIeXdhYXdDVExIZzdtQXpxQ2dLSVZzN2JUUVJEUE9FK25adkIzVU16KzVHUEJsK0lrRDJuUG9VRkJsNVBLa3lwZVlMd3RyenRYUm1LU0sxVEphSnRIZWljdkppVTJHL1B0cW1ZY3U3aWI0RmVjckxGb2dwUExNWXdVbHplY3NEZE45a2NmWWxGY1c2SmtuS3c1b1hJemtVT0xmMFZMQzY4VHBUbGFhSm5hdkZRNmYyekpoSk5PRGFIMUtkRWg4OWhmR0xqN0FQL0d5NWZHbW1YZk81bXduckI2ZThTbEt1Q2paMDNvckIxekJTYUhiMVpRMEFRZm4vaW1nd3lvNmlDYUEvRnMwS3EyR1JvSHNTV0V4YmQwTmo5ZmpmdmNmUXdYSTUzdXRmdHB2T3lJVU1USm4xaEloRis3TkswanpwYkFYakd3NGFsWkJET0J2UUl0RitUNmVqNmxseTdmcXo0ZmNRZTRwT3g5b0x0TndpUkV5RHZ2WjFRYnZsV0lsN2prMzJvdjZnRHo3dG14a05sbStza0JiZ0NZckt5Qmd5SC9wYmYyazdKRWJVbVhkdVFlZ1hTRTBVQjB3Ykw3eUNpRzhNTmpoSmpWZEU1WW0xUTQwTTFNbHdVeGo1Rkw0bExyWFdpZUlCMWJNZ3ZwMkFEcWJIZ1dMQkhUMmhuR1U2akRlQmRHRGRsT0R6WGlZNTVTWEQwNnFPZWdKU2s0WWJ3bDZSdlFpdTF3ZEFSNzlZQjdlODRZWEFqY3RjQ1ZNY1JFbFB6R3dhOW5pL0pnamVKakZ2cEpTQlJObkZDS1NwTkVieUJzV3hEeDlxQTFUdnBGR1pmem91SmN1ZHZ6NCtPengwUHhnSUlrNWFBbVBIRHk5blVPVXRXN0RpMVhtUjdEaHdYYlE1T3ZoWXc3VGRZWDViYy9hYWhOSVBUMFVJTzNHVmlLdGMwRlBFTTQ1M3lwbXJtdngvbHAxd05lQ1VTWWpqM01ILzhkcUdDWEk4K3ZQVjk3c216bVRpcVZwZk5rejA0RWhJREZuTFBZZmVKbGJCY0FrNEtFRVJWS25kbGIxUUdySkg0eVNXdlM4Njk1cFczWUhhejVMQWpGdEF2Vnk3K2VQNVhQSjArdlEwVUJXdGgveFFSMXY1NXg1VUlhaWF3V3d2azVnL3NEaHQvSGg2bUFCV1lnSWpKbHdZNTZiQlBsdUdhbmloN0IxTDRKcXVmNE5aRDBVeUFoYm9YQUE3YlVQVkRBcGdmQnluemNZdlAxSGVPcEo3MEVsanNOM0ZUcTZmUGV5ZWM4azN0ZFl2V0tNd0piWWNYU2UwcmVpWDg3NzlleXA0U1hxQk53NTJuYmRKalR4K1hlL2dDL2VsSm1xR1U1Q1FjV21uYVlvU2c5cVJ4akIya3NsanVlZTlWVWUwVkpHRE1WeitDT0ducGJjT0RnMVRDL3QxYVU0WkZEdytUTi9IdmtmQ0plNVJmNDVCQXNGYkl4YnA2aGtsTENpc1ZHUkg0UzJmYlYxbnhsZXlEd01BZm5ONllqM0hHMWNhMjFnSmtIdEhUckptV2VZSTBwcWlQNXpYWWlNNXB1Yko4c1hQNFNoaHo4L2JTSmlKRm16L2ZKOHFEVEcyMUs0UE4rQk51Q3dRYU41Q1lPVkFyRjNCblZlR3g3ZE1QK1lxcCsrSk51bU1mN1RZbk53V3RrMGtXM29qdXcrYnJSRllmSHYrTmJibWZ6aDYwb3NlSzkwOE1nbEZqWWJCaWJxN290eTZpT01pSjNKVFhRQTdqVEY0SWxrM2F6ZlExK0ppR3RmQ3AzVVgyR1hNbTBiUERxOXBxNkVhS2tDRkpKUWh4UXJjRlNNZy9vaVEzOUJGSGUvVUcvNGRpRGJKTjJsZEtzcmpSOGptSjZheGpYTGsxc2tKT1dlZUJoak1xWkMvN0xiWlVSUUYwdkd6Qm9xRkdlbVh5Qlkxb0VVWlBWSnNkYTNBTEY3VXV5b3BPQUpRMVFucEFvaGg1ZWJ0eFB5bXVnM0dkSGgrWmVPTk54US9obHJxTk9WcVFTNHczM0VVN0FSRm9Tc3g1dzNMQzhkK1VJaE9xa1p0K0tIRUFVcVFEeGpXNnZmU2dZbDRqd1JZTnJCYzgwK0oyMEZGekdnTWlsV0dwZlFOanhuUTJTOURpVFJTM3dKNUFodzJTS0JNa3h1Tk05NWFmWXVydWxSNTZwL0QvYjFndER4dHZoWHJObDB5RjFDRkYvQkRqYjFzNnc1T2YxaWxIYU8vRFhYQWU2MVdDRzlxNHFJblowUmFWOTJCeUtSMkJzYk9JVDFFc2k2T1FlUjZrT3NDbnZrcGFIWUw3eHhRR2ZEU2pFOE1Xa0M3UHVxY1NCK0FBQUtoTXU1cTZKazgweForZi9hVG1wMWR4YzdnZndKenVDQTVNZHlmUitqNllJd3p6WEtoVE1IWFl4dnNFRDNhL3ZVMDB6SThLc1hKSjRPeU5raFNGcjdTRW1ZK0U4NWdOalp1cU5MR2phZkk5N2lMRzZxdHZIYXZVclErNGVBZUt2MGx4VjBMZ2ZoWXRDa01URVpXUStRZi9ZZkp5YnNxOERlKzlaTmt4WWRPUDdnYms4VEN1UUpLZUROSEUvcW9CVFcxaG1qZ3J4V04rTkVkWTllaUU3b2d3cVZua2FtL2NaeWduejVkTmxxdERUQWtRdVRKWXpiMld4dE9IWFNvSTA3ZEpLUlBQYzArNmtLQWF3Rzc2d0wzOGxQQWRTczVtbUVOZHpOcERDSnE2VUdqSmxlRHdnMTZSeTdxK1dTcDA4QVJwdzFrMlJ6dk9qc0NDeGlYKzc1eXM4M1A5QU5jVGhybFZoekZkcVJ2VTBRNmE4U3A2MkhrU2hnUkV6aFdVTS9UaTlTUDlWOUJwRGpVSXB6Rm5rbWswM0JqTnBJUkZmbkRVeE5YekFVZFpiazNkbWdmbXI4NkptWE9ZenFUTWd1R21BVzg3Z21iWlVHTCtMbitoVVJFZUtTVGhzTnBWMnlnRzZLU0VJK0dUMERjQzUvOUFDUTZ1MHRRT3czZGRwWXZOTDdMUVRiVklsZHlVUXN1WFBiUi9LMG9Zb1RFNDd4WktiTWlOdVNtYXdHeXBQVkQycmovNFRzNWdBRTAzdGhvMW44MUtaSjd6TEhxMXVzVExzMms2WWNMelRHd2VEa2hKR1lhV00wanYzYjlrUUVLeEE4eEl0d0xkK2NDclBBTGNCUlJYdjRBeGRQcDZmS1dPRFRMMFkvN0VNdzlKWUFMek9tM1doZ0NRL09vZWNUN0QraGVMeXhVWEkwdWFZeDlJb0RtNTdNV3dJQlRkL2UzRlNPa3piL2Era0lPdGZNVFNRd3ZuUlV2MjJ4UWVLQ0V2M1l4NUZ3Y29CUGZKQ3B3eFBhY1Q3SzdYZWZnR2hiWVFDVC9RRlZvOEJNVE9vODV1WEJXcDYvR1oiLCJtYWMiOiJmZGFiMWYyOTc4ZTNmODY3OTBiODMxZThjZTFmM2MxMGExMjVhMjU0NDQ1ODNmMmNjMDhmZTg3Y2UzM2U5YjQ5IiwidGFnIjoiIn0=";

    /**
     * Соотношение статуса старой заявки к идентификтору нового статуса
     * 
     * @var string
     */
    protected $stateToStatusIdCrypt = "eyJpdiI6IjkxUC9Qa3g3TUVZaHU0REF6Y0c5bGc9PSIsInZhbHVlIjoiVDNHN2t3anFnOHhSMU9tVkdZMHNoMzBBMWtEcHB2SitBT05GQTJhZksvTk0yRWJob3BCM3RTZ0R4ZGRnL0poQ00yaVhVNENqcURrVVBxY2FwSk1zcVhLTk5ZUkUyQlQ2NkFyWTRsWUhER0h6eXRVS1hBMTlMbE5Gbkpnb29LNGZLU1ZUaVEyUEdKMjkvRlh6OUsvZnVneldSazc0cW1oTGNqUjEwbEhud1VlMlZUSE9vRFFKQ3dCdHRoaWhhZThTQU5QSkpxS2RxUzdPaGdiTms0RFlsMFpwWXRwVndJYi9XZXNaR2lPb3FEM0JqTlB1dGVFd1RlQmFGRE1BakpybDNIY2c4aUpTdXFQTDVjakc5SUJQRUFuME5hUmlmNThIcjZHSzNHOFhvc0d1Ni9neERnUmlzUFJ0OG9KcW5jWEF2MDJQZ245N2V5R2R1Y1ZVa1hzcUM3TGZNWlFBQjJpUDVwOFZsdG1TWURkakVUMXNLUHltVTJYVUlsbjNMTFR6IiwibWFjIjoiMDYwYjdmYzcxOTcxODQ4ZjM1NDNhYjY4MDcyZGQwZjU1NDg2YzA5ZTliMjQxNzM3ZDZmZDgyYTZjYmUzMzg3NSIsInRhZyI6IiJ9";

    /**
     * Идентификаторы секторов
     * 
     * @var array
     */
    protected $sectorIdToId = [
        1 => 1,
        2 => 1,
        3 => 2,
        4 => 2,
        5 => 2,
        6 => 2,
        7 => 3,
    ];

    /**
     * Сопостовление идентификаторов источников
     * 
     * @var array
     */
    public function sourceToSource()
    {
        return decrypt($this->sourceToSourceCrypt);
    }

    /**
     * Сопостовление ресурсов источника
     * 
     * @var array
     */
    public function sourceResourceToRecource()
    {
        return decrypt($this->sourceResourceToRecourceCrypt);
    }

    /**
     * Соотношение статуса старой заявки к идентификтору нового статуса
     * 
     * @return array
     */
    public function stateToStatusId()
    {
        return decrypt($this->stateToStatusIdCrypt);
    }

    /**
     * Поиск номеров телефона в старой заявке
     * 
     * @param  \App\Models\CrmMka\CrmRequest $row
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

        return array_values(array_unique($phones ?? []));
    }

    /**
     * Определение типа заявки
     * 
     * @param  \App\Models\CrmMka\CrmRequest $row
     * @return string
     */
    public function getQueryType($row)
    {
        return $row->typeReq == "Звонок" ? "call" : "text";
    }

    /**
     * Определение сектора
     * 
     * @param  \App\Models\CrmMka\CrmRequest $row
     * @return null|int
     */
    public function getSectorId($row)
    {
        $sector = $row->{'call-center'};

        if ($sector === "" or $sector === null)
            return null;

        return $this->sectorIdToId[(int) $sector] ?? null;
    }

    /**
     * Определяет идентификатор сектора по идентифкатору
     * 
     * @param  int $id
     * @return int|null
     */
    public function getSectorIdFromId($id)
    {
        return $this->sectorIdToId[(int) $id] ?? null;
    }

    /**
     * Определение источника старой заявки
     * 
     * @param  \App\Models\CrmMka\CrmRequest $row
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
     * @param  \App\Models\CrmMka\CrmRequest $row
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
     * Определение идентификатора статуса заявки
     * По умолчнию будет бракованная заявка, чтобы обнулить её при следующем поступлении
     * 
     * @param  \App\Models\CrmMka\CrmRequest|\App\Models\CrmMka\CrmNewRequestsState $row
     * @return null|int
     */
    public function getStatusId($row)
    {
        if ($this->changeStatusCall($row))
            $row->state = "bk";

        return $this->stateToStatusId[$row->state] ?? 6;
    }

    /**
     * Определяет необходимость смены статуса созвона на БК
     * 
     * @param  \App\Models\CrmMka\CrmRequest|\App\Models\CrmMka\CrmNewRequestsState $row
     * @return bool
     */
    public function changeStatusCall($row)
    {
        try {

            if (!$datetime = $this->getEventAt($row))
                return false;

            if ($row->state == "sozvon" and now()->subMinutes(15) > now()->create($datetime)) {
                return true;
            }
        } catch (Exception) {
        }

        return false;
    }

    /**
     * Определение времени события
     * 
     * @param  \App\Models\CrmMka\CrmRequest $row
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
     * @param  \App\Models\CrmMka\CrmRequest $row
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
     * @param  \App\Models\CrmMka\CrmRequest $row
     * @param  \App\Models\RequestsRow $new
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
     * @param  \App\Models\CrmMka\CrmRequest $row
     * @param  \App\Models\RequestsRow $new
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
     * @param  \App\Models\RequestsRow $new
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
}
