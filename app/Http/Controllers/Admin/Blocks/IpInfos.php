<?php

namespace App\Http\Controllers\Admin\Blocks;

use App\Exceptions\Exceptions;
use App\Http\Controllers\Controller;
use App\Models\IpInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class IpInfos extends Controller
{
    /**
     * Создание экземпляра объекта
     * 
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(
        protected Request $request
    ) {
    }

    /**
     * Вывод информации по IP-адресу
     * 
     * @param null|string $ip
     * @return array
     */
    public function getIpInfo($ip = null)
    {
        if (!$ip = ($ip ?: $this->request->ip))
            throw new Exceptions("IP адрес не определен");

        $info = $this->checkInfoData($ip);

        return [
            'ip' => $ip,
            'ipinfo' => $info,
            'stats' => (new Statistics($this->request))->getStatisticIp($ip)
        ];
    }

    /**
     * Поиск информации по IP
     * 
     * @param string $ip
     * @return array
     */
    public function checkInfoData($ip)
    {
        $url = "http://ip-api.com/json/" . $ip;

        $info = IpInfo::firstOrCreate([
            'ip' => $ip,
        ]);

        if ($info->checked_at)
            return $info->toArray();

        try {

            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])
                ->withOptions([
                    'verify' => false, // Отключение проверки сетификата
                ])
                ->get($url);

            if ($response->getStatusCode() != 200)
                return $info->toArray();

            $info->info = $response->json();
            $info->country_code = $info->info['countryCode'] ?? null;
            $info->region_name = $info->info['regionName'] ?? null;
            $info->city = $info->info['city'] ?? null;
            $info->checked_at = now();

            $info->save();
        }
        // Исключение при отсутсвии подключения к серверу
        catch (\Illuminate\Http\Client\ConnectionException) {
            return $info->toArray();
        }
        // Исключение при ошибочном ответе
        catch (\Illuminate\Http\Client\RequestException) {
            return $info->toArray();
        }

        return $info->toArray();
    }
}
