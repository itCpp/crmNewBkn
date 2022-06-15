<?php

namespace App\Http\Controllers\Chats\ForNewCrm;

use App\Events\Chat\NewMessage;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use SplFileInfo;

trait MessagesTrait
{
    /**
     * Отправка сообщения
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        return response()->json(
            $this->sendMessageProcess($request)
        );
    }

    /**
     * Обработка запроса отправки сообщения
     * 
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function sendMessageProcess(Request $request)
    {
        $request->chat_id = (int) $request->chat_id;

        $message = ChatMessage::create([
            'user_id' => $request->user()->id,
            'chat_id' => (int) $request->chat_id,
            'type' => $request->type,
            'message' => Controller::encrypt($request->message),
            'body' => $this->getBodyMessage($request),
        ]);

        $message->message = $request->message;
        $message = $message->toArray();

        $room = $this->getChatRoom($request->chat_id, true);

        broadcast(new NewMessage($message, $room ?: [], $room['users_id']))->toOthers();

        // if ($message->body)
        //     UploadFilesChatJob::dispatch($message);

        $message['my'] = true;

        return [
            'message' => $message,
            'room' => $room,
        ];
    }


    /**
     * Формирование массива тела сообщения с вложениями
     * 
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function getBodyMessage(Request $request)
    {
        $body = [];

        if (!$request->urls)
            return $body;

        if (is_array($request->urls)) {
            foreach ($request->urls as $url) {
                $body[] = $this->bodyRowTempate([
                    'name' => (new SplFileInfo($url))->getBasename(),
                    'url' => $url
                ]);
            }
        }

        return $body;
    }

    /**
     * Элемент объекта тела вложений сообщения
     * 
     * @param  array
     * @return array
     */
    public function bodyRowTempate($data = [])
    {
        return array_merge($data, [
            'name' => $data['name'] ?? null,
            'hash' => null,
            'type' => null,
            'mimeType' => null,
            'loading' => true,
        ]);
    }
}
