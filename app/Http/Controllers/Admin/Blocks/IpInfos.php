<?php

namespace App\Http\Controllers\Admin\Blocks;

use App\Exceptions\Exceptions;
use App\Http\Controllers\Controller;
use App\Models\IpInfo;
use App\Models\Company\BlockHost;
use App\Models\Company\StatVisitSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
// use Illuminate\Support\Str;

class IpInfos extends Controller
{
    /**
     * Данные об информации по IP
     * 
     * @var array
     */
    protected $info = [];

    /**
     * Массив ключей, с данными
     * 
     * @var array
     */
    protected $keys = [
        'ipApi',
        'whoisRestApi'
    ];

    /**
     * Создание экземпляра объекта
     * 
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(
        protected Request $request,
    ) {
    }

    /**
     * Вывод только информации об IP
     * 
     * @param null|string $ip
     * @return array
     */
    public function getIpInfo($ip = null)
    {
        if (!$ip = ($ip ?: $this->request->ip))
            throw new Exceptions("IP адрес не определен");

        return $this->checkInfoData($ip);
    }

    /**
     * Вывод информации по IP-адресу и его статистику
     * 
     * @param null|string $ip
     * @return array
     */
    public function getIpInfoAndStat($ip = null)
    {
        if (!$ip = ($ip ?: $this->request->ip))
            throw new Exceptions("IP адрес не определен");

        $info = $this->checkInfoData($ip);

        $this->statistics = new Statistics($this->request);
        $general_stats = $this->statistics->getStatistic($ip)[0] ?? [];

        $sites_stats = $this->sitesStatsFromIp($ip);

        return [
            'ip' => $ip,
            'ipinfo' => $info,
            'generalStats' => $general_stats,
            'sitesStats' => $sites_stats,
            // 'stats' => (new Statistics($this->request))->getStatisticIp($ip),
            'stats' => [],
            'textInfo' => $this->getTextIpInfo(),
            'block' => BlockHost::where('host', $ip)->first(),
        ];
    }

    /**
     * Раздельная статистика посещений по сайтам
     * 
     * @param string $ip
     * @return array
     */
    public function sitesStatsFromIp($ip)
    {
        $sites = StatVisitSite::select('site')
            ->where('ip', $ip)
            ->where('site', '!=', null)
            ->distinct()
            ->get()
            ->map(function ($row) {
                return $row->site;
            });

        foreach ($sites as $site) {

            $this->statistics->sites = [$site];
            $this->statistics->data = [];
            $this->statistics->ips = [$ip];

            // if (!Str::substrCount($site, 'www.'))
            //     $this->statistics->sites[] = "www." . $site;

            if ($row = $this->statistics->getStatistic($ip)[0] ?? null)
                $data[] = array_merge($row, ['site' => $site]);
        }

        return collect($data ?? [])->sortByDesc('visitsAll')->values()->all();
    }

    /**
     * Проверка подробной информации об IP
     * 
     * @param string $ip
     * @return array
     */
    public function checkInfoData($ip)
    {
        $row = IpInfo::firstOrCreate([
            'ip' => $ip,
        ]);

        $this->info = (array) $row->info;

        if (!($row->info['ipApi'] ?? null)) {
            $row = $this->checkIpApiComData($row);
        }

        if (!($row->info['whoisRestApi'] ?? null)) {
            $row = $this->checkWhoisRestApi($row);
        }

        $row->info = $this->info;
        $row->save();

        return $row->toArray();
    }

    /**
     * Поиск информации по IP на сервисе ip-api.com
     * 
     * @param IpInfo $row
     * @return IpInfo
     */
    public function checkIpApiComData($row)
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])
                ->withOptions([
                    'verify' => false, // Отключение проверки сетификата
                ])
                ->get("http://ip-api.com/json/" . $row->ip);

            if ($response->getStatusCode() != 200)
                return $row;

            $ipApi = $response->json();

            $row->checked_at = now();

            $this->info['ipApi'] = [
                'name' => "ip-api.com",
                'datetime' => $row->checked_at,
                'data' => $ipApi
            ];

            if ($ipApi['countryCode'] ?? null)
                $row->country_code = $ipApi['countryCode'];

            if ($ipApi['regionName'] ?? null)
                $row->region_name = $ipApi['regionName'];

            if ($ipApi['city'] ?? null)
                $row->city = $ipApi['city'];
        }
        // Исключение при отсутсвии подключения к серверу
        catch (\Illuminate\Http\Client\ConnectionException) {
            return $row;
        }
        // Исключение при ошибочном ответе
        catch (\Illuminate\Http\Client\RequestException) {
            return $row;
        }

        return $row;
    }

    /**
     * Проверкаи информации на сервисе WHOIS REST API
     * 
     * @param IpInfo $row
     * @return IpInfo
     */
    public function checkWhoisRestApi($row)
    {
        try {
            $response = Http::withOptions([
                'verify' => false,
            ])->get("http://rest.db.ripe.net/search?query-string=" . $row->ip);

            if ($response->getStatusCode() != 200)
                return $row;

            $xml = simplexml_load_string($response->body());
            $array = json_decode(json_encode($xml), TRUE);

            foreach ($array['objects'] as $object) {
                foreach ($object as $row2) {
                    foreach ($row2['attributes'] as $row3) {
                        foreach ($row3 as $row4) {
                            $data[$row4['@attributes']['name']][] = $row4['@attributes']['value'];
                        }
                    }
                }
            }

            $row->checked_at = now();

            $this->info['whoisRestApi'] = [
                'name' => "WHOIS REST API",
                'datetime' => $row->checked_at,
                'data' => $data ?? [],
            ];
        }
        // Исключение при отсутсвии подключения к серверу
        catch (\Illuminate\Http\Client\ConnectionException) {
            return $row;
        }
        // Исключение при ошибочном ответе
        catch (\Illuminate\Http\Client\RequestException) {
            return $row;
        }

        return $row;
    }

    /**
     * Преобразование массива данных в текст для вывода информации
     * 
     * @return array
     */
    public function getTextIpInfo()
    {
        foreach ($this->info as $key => $info) {
            if (in_array($key, $this->keys) and !empty($info['data'])) {
                $data[] = array_merge(
                    $info,
                    ['data' => $this->arrayToString($info['data'])]
                );
            }
        }

        return $data ?? [];
    }

    /**
     * Преобразования массива в текст
     * 
     * @param string|array $data
     * @return string
     */
    public function arrayToString(string|array $data)
    {
        $string = "";

        if (is_string($data)) {
            return $string .= "{$data}\r\n";
        }

        foreach ($data as $key => $list) {

            if (is_array($list)) {

                if ($this->is_array_list($list)) {
                    foreach ($list as $str) {
                        $string .= "{$key}: {$str}\r\n";
                    }
                } else {
                    $string .= $this->arrayToString($list);
                }
            } else {
                $string .= "{$key}: {$list}\r\n";
            }
        }

        return $string;
    }
}
