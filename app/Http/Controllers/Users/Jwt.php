<?php

namespace App\Http\Controllers\Users;

use Illuminate\Support\Str;

class Jwt
{
    /**
     * Заголовок токена
     * 
     * @var array
     */
    protected $header = [
        'alg' => "HS256",
        'typ' => "JWT",
    ];

    /**
     * Воззвращает ключ шифрования
     * 
     * @return string
     */
    private function secretKey()
    {
        return Str::replace('base64:', '', env('APP_KEY', ""));
    }

    /**
     * Формирвоание части токена с заголовком
     * 
     * @return string
     */
    public function header()
    {
        return base64_encode(json_encode($this->header, true));
    }

    /**
     * Формирвоание части токена с нагрузкой
     * 
     * @param  array $payload
     * @return string
     */
    public function payload($payload)
    {
        return base64_encode(json_encode($payload, true));
    }

    /**
     * Формирвоание подписи
     * 
     * @param  string $string
     * @return string
     */
    public function signature(string $string)
    {
        $hash = base64_encode(hash_hmac("sha256", $string, $this->secretKey(), true));

        return Str::replace(".", "_", $hash);
    }

    /**
     * Создание токена
     * 
     * @param  array $payload
     * @return string
     */
    public function createAccessToken($payload)
    {
        $header = $this->header();
        $payload = $this->payload($payload);
        $signature = $this->signature($header . "." . $payload);

        return "{$header}.{$payload}.{$signature}";
    }

    /**
     * Првоерка токена
     * В случае успешной проверки вернет токен авторизации, присутсвующей в массиве данных
     * 
     * @param  string $token
     * @return int|null
     */
    public function verifyToken($token)
    {
        $jwt = explode(".", $token);

        if (count($jwt) != 3)
            return null;

        $jwt = array_combine(['header', 'payload', 'signature'], $jwt);

        $header = json_decode(base64_decode($jwt['header']), true);
        $payload = json_decode(base64_decode($jwt['payload']), true);

        if (!is_array($header) || !is_array($payload))
            return null;

        if ($jwt['signature'] != $this->signature($jwt['header'] . "." . $jwt['payload']))
            return null;

        return $payload['token'] ?? null;
    }
}
