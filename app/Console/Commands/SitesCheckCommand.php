<?php

namespace App\Console\Commands;

use App\Models\Base\TelegramMailingList;
use App\Models\RequestsSourcesResource;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SitesCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sites:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Проверяет статусы сайтов';

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
        $this->length = 0;
        $this->sites = [];
        $this->errors = [];

        RequestsSourcesResource::whereType('site')
            ->whereCheckSite(true)
            ->get()
            ->each(function ($row) {

                $site = idn_to_utf8($row->val);

                if (mb_strlen($row->val) != strlen($row->val))
                    $row->val = idn_to_ascii($row->val);

                if ($this->length < $length = mb_strlen($site))
                    $this->length = $length;

                $this->sites[] = $row->val;
            });

        $sites = collect($this->sites)->sort()
            ->unique()
            ->values()
            ->all();

        if (!count($sites))
            return $this->info("Сайтов для проверки не найдено");

        $this->length += 10;

        foreach ($sites as $site) {
            $this->checkSite($site);
        }

        if (count($this->errors)) {
            $this->errorHandler();
        }

        return 0;
    }

    /**
     * Connect tot site
     * 
     * @return null
     */
    public function checkSite($domain)
    {
        $site = idn_to_utf8($domain);

        $message = "<options=bold>{$site}</> ";

        for ($i = mb_strlen($site); $i <= $this->length; $i++)
            $message .= ".";

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'User-Agent' => "CPP CRM MKA (" . env("APP_URL") . ")",
                    'Host' => $domain,
                ])
                ->withOptions([
                    'verify' => false,
                ])
                ->get($domain);

            $status = $response->status();

            if ($status != 200) {
                $this->errors[] = [
                    'site' => $site,
                    'domain' => $domain,
                    'error' => "Сервер с сайтом вернул статус " . $status,
                ];
            }
        } catch (Exception $e) {

            $status = "Err";

            $this->errors[] = [
                'site' => $site,
                'domain' => $domain,
                'error' => $e->getMessage(),
            ];
        }

        $color = $status == 200 ? "green" : "red";

        $this->line("{$message} <fg={$color};options=bold>{$status}</>");

        return;
    }

    /**
     * Обработка ошибок
     * 
     * @return null
     */
    public function errorHandler()
    {
        $this->newLine();

        $message = "*ОШИБКА НА САЙТАХ*\n\n";

        foreach ($this->errors as $row) {

            $message .= "🔗 {$row['site']}\n";
            $message .= "🟥 `{$row['error']}`\n\n";

            $this->line("<fg=red;options=bold>{$row['site']}</>");
            $this->error($row['error']);
        }

        $this->newLine();

        $api_url = "https://api.telegram.org/bot" . env('TELEGRAM_API_TOKEN', "not_api_token") . "/sendMessage";

        $chats_id = TelegramMailingList::select('personal.telegram')
            ->join('personal', 'personal.pin', '=', 'telegram_mailing_lists.pin')
            ->where('type_id', 'crm_check_sites_result')
            ->distinct()
            ->get()
            ->map(function ($row) {
                return $row->telegram;
            })
            ->toArray();

        foreach ($chats_id as $chat_id) {

            try {
                Http::withOptions(['verify' => false])
                    ->post($api_url, [
                        'text' => $message,
                        'chat_id' => $chat_id,
                        'parse_mode' => "Markdown",
                    ]);
            } finally {
            }
        }

        return;
    }
}
