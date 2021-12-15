<?php

namespace App\Http\Controllers\Chats;

use App\Http\Controllers\Controller;
use App\Models\ChatFile;
use Illuminate\Http\Request;

class Chat extends Controller
{
    /**
     * Загрузка страницы чата
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function startChat(Request $request)
    {
        return response()->json(
            (new StartChat($request))->start()
        );
    }

    /**
     * Поиск сотркдника или чат группы
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function searchRoom(Request $request)
    {
        return response()->json(
            (new StartChat($request))->search()
        );
    }

    /**
     * Вывод сообщений чата
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getMessages(Request $request)
    {
        return response()->json(
            (new Messages())->get($request)
        );
    }

    /**
     * Вывод сообщений чата
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function sendMessage(Request $request)
    {
        return response()->json(
            (new Messages())->sendMessage($request)
        );
    }

    /**
     * Выдача файла для чата
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $hash
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public static function file(Request $request, $hash)
    {
        return (new Files)->responseFile($request, $hash);
    }
}
