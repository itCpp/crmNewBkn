<?php

namespace App\Console\Commands;

use Exception;
use App\Http\Controllers\Controller;
use App\Models\Incomings\IncomingEvent;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Log;

class EventsRecryptCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:recrypt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rewriting external events';

    /**
     * Промежуточный ключ
     * 
     * @var string
     */
    protected $key;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->key = base64_decode(str_replace("base64:", "", env('APP_KEY_IN')));

        for ($i = 0; $i <= 10; $i++) {
            try {
                if ($id = $this->eventRecrypt())
                    Log::channel('cron_events_recrypt')->debug('Rewriting external events id: ' . $id);
            } catch (\Exception $e) {
                Log::channel('cron_events_recrypt')->emergency($e->getMessage());
            }
        }

        return 0;
    }

    /**
     * Метод перешифрования
     * 
     * @return int|null
     */
    public function eventRecrypt()
    {
        $event = IncomingEvent::where('recrypt', '=', null)
            ->where('created_at', '<=', now()->addMinutes(-60))
            ->first();

        if (!$event)
            return null;

        $crypt = new Encrypter($this->key, config('app.cipher'));
        $data = Controller::decrypt($event->request_data, $crypt);

        $event->request_data = Controller::encrypt($data);
        $event->recrypt = now();

        if (!$event->session_id) {
            if ($event->api_type == "Asterisk" and !empty($data['ID'])) {
                $event->session_id = $this->createCallId($data['ID']);
            } else if ($event->api_type == "RT" and !empty($data['session_id'])) {
                $event->session_id = $data['session_id'];
            }
        }

        $event->save();

        return $event->id;
    }

    /**
     * Формирование уникального id по отпечатку времени
     * 
     * @param string $id
     * @return string
     */
    public static function createCallId($id)
    {
        $id = $id ?: microtime(1);

        $parts = explode(".", $id);
        $hash = "";

        foreach ($parts as $part) {
            $hash .= md5($part);
        }
        $hash .= md5($hash);
        $hash .= md5($hash);

        $uuid = "";

        $uuid .= substr($hash, 0, 8);
        $uuid .= "-" . substr($hash, 7, 4);
        $uuid .= "-4" . substr($hash, 11, 3);
        $uuid .= "-8" . substr($hash, 15, 3);
        $uuid .= "-" . substr($hash, 19, 12);

        return $uuid;
    }
}
