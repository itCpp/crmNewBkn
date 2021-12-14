<?php

namespace App\Jobs\Chat;

use App\Http\Controllers\Chats\Files;
use App\Models\ChatMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UploadFilesChatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Экземпляр модели сообщения
     * 
     * @var \App\Models\ChatMessage
     */
    protected $message;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\ChatMessage $message
     * @return void
     */
    public function __construct(ChatMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->files = new Files();

        $body = [];

        foreach ($this->message->body as $file) {

            if ($file['url'] ?? null)
                $file = $this->uploadFromUrl($file);

            $body[] = $file;
        }

        $this->message->body = $body;
        $this->message->save();
    }

    /**
     * Загрузка файла через ссылку
     * 
     * @param array
     * @return array
     */
    public function uploadFromUrl($file)
    {
        try {
            $uploaded = $this->files->uploadFromUrl($file['url']);

            $file['mimeType'] = $uploaded->mime_type ?? null;
            $file['size'] = $uploaded->size ?? null;
            $file['name'] = $uploaded->original_name ?? null;
            $file['hash'] = $uploaded->hash ?? null;
            $file['extension'] = $this->files->mime2ext($uploaded->mime_type);
            $file['type'] = $this->files->mime2type($uploaded->mime_type);

            if (isset($file['error']))
                unset($file['error']);

        } catch (\Exception $e) {
            $file['error'] = $e->getMessage();
        }

        $file['loading'] = false;

        return $file;
    }
}
