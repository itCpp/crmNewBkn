<?php

namespace App\Console\Commands;

use Exception;
use App\Http\Controllers\Controller;
use App\Models\Incomings\IncomingEvent;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Log;

class RecryptExternalEvents extends Command
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

        $this->key = base64_decode(str_replace("base64:", "", env('APP_KEY_IN')));
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $id = $this->eventRecrypt();
            Log::channel('eventsrecrypt')->debug('Rewriting external events id: ' . $id);
        } catch (\Exception $e) {
            Log::channel('eventsrecrypt')->emergency($e->getMessage());
        }

        return 0;
    }

    /**
     * Метод перешифрования
     * 
     * @return int
     */
    public function eventRecrypt()
    {
        $event = IncomingEvent::where('recrypt', '=', null)
            ->where('created_at', '<=', now()->addMinutes(-60))
            ->first();

        $crypt = new Encrypter($this->key, config('app.cipher'));
        $data = Controller::decrypt($event->request_data, $crypt);

        $event->request_data = Controller::encrypt($data);
        $event->recrypt = now();

        $event->save();

        return $event->id;
    }
}
