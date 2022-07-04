<?php

namespace App\Console\Commands;

use App\Http\Controllers\Requests\Synhro\Webhoock;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class RequestRetryHoockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request:retryhoock
                            { data? : Зашифрованные данные ответа обработки хука }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Повторяет обработку хука';

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
        if (!$data = $this->argument('data'))
            $data = $this->ask('Введите зашифрованную строку данных входящего хука...');

        try {
            $data = decrypt($data);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        $ip = $data['ip'] ?? null;
        $token = $data['token'] ?? null;
        $request = $data['request'] ?? [];
        $headers = $data['headers'] ?? [];
        $type = $data['method'] ?? null;

        $server = [
            'remote-addr' => $ip,
        ];

        foreach ($headers as $key => $value) {
            $server[$key] = $value;
        }

        $query = new Request(
            query: $request,
            server: $server,
        );

        $response = (new Webhoock)->index($query, $token, $type);

        if ($response instanceof \Illuminate\Http\JsonResponse)
            dump($response->getData(true));

        return 0;
    }
}
