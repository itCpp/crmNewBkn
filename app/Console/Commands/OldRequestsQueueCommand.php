<?php

namespace App\Console\Commands;

use App\Console\MyOutput;
use App\Http\Controllers\Controller;
use App\Models\CrmMka\CrmRequestsQueue;
use App\Models\RequestsQueue;
use Illuminate\Console\Command;

class OldRequestsQueueCommand extends Command
{
    use MyOutput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'old:requestsqueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Перенос очереди текстовых заявок';

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
        $this->info("Перенос очереди текстовых заявок");

        $bar = $this->output->createProgressBar(CrmRequestsQueue::count());
        $bar->start();

        $stop = false;
        $this->step_id = 0;

        while (!$stop) {

            $stop = !$this->handleStep();
            $bar->advance();
        }

        $bar->finish();

        $this->newLine(2);

        return 0;
    }

    /**
     * Выполнение одного шага
     * 
     * @return bool
     * 
     * @todo Найти номер заявки, созданной от очереди
     */
    public function handleStep()
    {
        if (!$row = CrmRequestsQueue::where('id', '>', $this->step_id)->first())
            return false;

        $this->step_id = $row->id;

        $data = [
            'phone' => $row->phone,
            'client_name' => $row->name,
            'comment' => $row->comment,
            'site' => idn_to_utf8($row->site),
            'page' => $row->page,
            'utm_source' => $row->utm_source,
            'utm_medium' => $row->utm_medium,
            'utm_campaign' => $row->utm_campaign,
            'utm_content' => $row->utm_content,
            'utm_term' => $row->utm_term,
            'device' => $row->device,
            'region' => $row->region,
        ];

        $hash = "";

        foreach ($data as $key => $value)
            $hash .= (string) $key . (string) $value . ";";

        $data['from_old_crm'] = $row->toArray();

        $request_data = (object) Controller::encrypt($data);

        if ($row->pin_done !== null)
            $done_pin = (int) $row->pin_done > 0 ? $row->pin_done : "AUTO";

        if ($row->done == 1)
            $row->done = 2;
        else if ($row->done == 2)
            $row->done = 1;

        RequestsQueue::create([
            'request_data' => $request_data,
            'ip' => $row->ip,
            'site' => idn_to_utf8($row->site),
            'user_agent' => $row->user_agent ?: null,
            'done_pin' => $done_pin ?? null,
            'done_type' => $row->done,
            'done_at' => $row->done_time,
            'created_at' => $row->created_at,
            'hash' => md5($hash),
        ]);

        return true;
    }
}
