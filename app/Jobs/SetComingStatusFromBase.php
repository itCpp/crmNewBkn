<?php

namespace App\Jobs;

use App\Events\Requests\UpdateRequestEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Requests\Requests;
use App\Http\Controllers\Users\Worktime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\RequestsRow;
use App\Models\RequestsStory;
use App\Models\RequestsStoryStatus;
use Illuminate\Support\Facades\Log;

class SetComingStatusFromBase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  array $data
     * @param  string|null $ip
     * @return void
     */
    public function __construct(
        protected array $data,
        protected string|null $ip
    ) {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = array_merge($this->data, [
            'id' => $this->data['id'] ?? null,
            'pin' => $this->data['pin'] ?? null,
        ]);

        $pin = $data['pin'] ? "Base " . $data['pin'] : null;

        if (!$row = RequestsRow::find($data['id']))
            return Log::channel('comings')->error("Заявка не найдена", $data);

        $status_id = Controller::envExplode('STATISTICS_OPERATORS_STATUS_COMING_ID');

        if (!($status_id[0] ?? null))
            return Log::channel('comings')->error("Идентификатор статуса приход не определен в настройках", $data);

        if (in_array($row->status_id, $status_id))
            return Log::channel('comings')->error("Заявка уже имеет статус прихода", $data);

        $old_status = $row->status_id;
        $row->status_id = $status_id[0];
        $row->event_at = now();
        $row->save();

        $story = RequestsStory::create([
            'request_id' => $row->id,
            'row_data' => $row->toArray(),
            'request_data' => $data,
            'created_pin' => $pin,
            'ip' => $this->ip,
            'created_at' => now(),
        ]);

        if ($old_status != $row->status_id) {
            RequestsStoryStatus::create([
                'story_id' => $story->id,
                'request_id' => $row->id,
                'status_old' => $old_status,
                'status_new' => $row->status_id,
                'created_pin' => $pin,
                'created_at' => $story->created_at,
            ]);
        }

        $row = Requests::getRequestRow($row); // Полные данные по заявке

        // Отправка события об изменении заявки
        broadcast(new UpdateRequestEvent($row));

        if ($row->pin)
            Worktime::checkAndWriteWork($row->pin);

        return Log::channel('comings')->info("Статус прихода установлен для заявки", $data);
    }
}
