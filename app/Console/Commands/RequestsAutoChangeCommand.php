<?php

namespace App\Console\Commands;

use App\Events\AppUserPinEvent;
use App\Events\Requests\UpdateRequestEvent;
use App\Http\Controllers\Requests\Requests;
use App\Http\Controllers\Settings;
use App\Models\RequestsAutoChangeCount;
use App\Models\RequestsRow;
use App\Models\RequestsStory;
use App\Models\RequestsStoryStatus;
use App\Models\Status;
use Illuminate\Console\Command;

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

        $statuses = Status::where('settings->auto_change_id', '!=', null)
            ->get()
            ->each(function ($row) {
                $this->handleStep($row);
            });

        if (!count($statuses))
            $this->line(now()->format("[Y-m-d H:i:s]") . " No configured statuses for automatic change (<options=bold>auto_change_id</> setting)");

        if (!count($this->counts))
            $this->line(now()->format("[Y-m-d H:i:s]") . " No requests for change 💩");
 
        foreach ($this->counts as $pin => $count) {

            $row = RequestsAutoChangeCount::firstOrNew([
                'pin' => (int) $pin,
                'date' => now()->format("Y-m-d"),
            ]);

            $row->count += $count;
            $row->save();

            broadcast(new AppUserPinEvent([
                'type' => "auto_change_count",
                'message' => "Автоматически изменен статус заявок: {$count}",
                'count' => $count,
            ], $pin));
        }

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
        $column = $row->settings->auto_change_column ?? "event_at";
        $change = $row->settings->auto_change_id;

        RequestsRow::where('status_id', $row->id)
            ->where('status_id', '!=', $change)
            ->where($column, '<', now()->subMinute($minutes))
            ->orderBy('event_at')
            ->get()
            ->each(function ($row) use ($change) {

                if (!isset($this->counts[$row->pin]))
                    $this->counts[$row->pin] = 0;

                $this->counts[$row->pin]++;

                $status_old = $row->status_id;
                $row->status_id = $change;

                $row->save();

                // Логирование изменений заявки
                $story = RequestsStory::write(request(), $row);

                // Логирование изменения статуса
                if ($status_old != $row->status_id) {
                    RequestsStoryStatus::create([
                        'story_id' => $story->id,
                        'request_id' => $row->id,
                        'status_old' => $status_old,
                        'status_new' => $change,
                        'created_pin' => optional(request()->user())->pin,
                        'created_at' => now(),
                    ]);
                }

                $this->line(now()->format("[Y-m-d H:i:s]") . " Change status request id: <fg=green>{$row->id}</> (<options=bold>$status_old</> to <options=bold>$change</> id)");

                $row = Requests::getRequestRow($row); // Полные данные по заявке

                // Отправка события об изменении заявки
                broadcast(new UpdateRequestEvent($row));
            });

        return null;
    }
}
