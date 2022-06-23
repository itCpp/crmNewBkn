<?php

namespace App\Http\Controllers\Requests\Synhro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Webhoock extends Controller
{
    /**
     * Существующие методы
     * 
     * @var array
     */
    protected $methods = [
        "addPhone",
        "changeSector",
        "create",
        "firstComment",
        "hide",
        "pin",
        "save",
        "sbComment",
        "theme",
        "update",
    ];

    /**
     * Првоерка токена доступа
     * 
     * @param  string $token
     * @return boolean
     */
    public function virifyToken($token)
    {
        return env("ACCESS_TOKEN_SYNHRO") == $token;
    }

    /**
     * Обработка входящего запроса
     * 
     * @param  \Illuminate\Http\Request $request
     * @param  string $token
     * @param  string $type
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $token, $type)
    {
        Log::channel('webhoock_access')->info("webhoock/{$type}", [
            'real_ip' => $request->header('X-Real-Ip') ?: $request->ip(),
            'ip' => $request->ip(),
            'token' => $token,
            'request' => $request->all(),
            'headers' => $request->header(),
        ]);

        if (!$this->virifyToken($token))
            return response()->json(['message' => "Permission denied"], 403);

        return response()->json();
    }
}
