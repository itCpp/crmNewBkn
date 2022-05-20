<?php

namespace App\Console\Commands;

use App\Http\Controllers\Settings;
use App\Models\RequestsRow;
use App\Models\Status;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RequestsAutoChangeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'requests:autochange';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Автоматически меняет статус заявке, по истечению установленного времени';

    /**
     * Колчество минут до обнуления
     * 
     * @var int
     */
    const MINUTES = 15;

    /**
     * Количество смены статусов
     * 
     * @var array
     */
    protected $counts = [];

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
        if (!$this->minutes = (new Settings)->REQUESTS_AUTO_CHANGE_MINUTES)
            $this->minutes = self::MINUTES;

        Status::where('settings->auto_change_id', '!=', null)->get()
            ->each(function ($row) {
                $this->handleStep($row);
            });

        return 0;
    }

    /**
     * Выполнение смены
     * 
     * @param  \App\Models\Status $row
     * @return null
     */
    public function handleStep(Status $row)
    {
        $minutes = $row->settings->auto_change_minutes ?? $this->minutes;
        $change = $row->settings->auto_change_id;

        RequestsRow::where('status_id', $row->id)
            ->where('event_at', '<', now()->addMinute($minutes))
            ->orderBy('event_at')
            ->get()
            ->each(function ($row) use ($change) {

                if (!isset($this->counts[$row->pin]))
                    $this->counts[$row->pin] = 0;

                $this->counts[$row->pin]++;

                
            });

        return null;
    }
}
