<?php

namespace App\Console\Commands;

use App\Http\Controllers\Dev\RequestsMerge;
use App\Http\Controllers\Requests\RequestChange;
use App\Models\CrmMka\CrmNewRequestsState;
use App\Models\CrmMka\CrmNewRequestsStory;
use App\Models\CrmMka\CrmRequest;
use App\Models\RequestsRow;
use App\Models\RequestsStory;
use App\Models\RequestsStoryPin;
use App\Models\RequestsStoryStatus;
use Illuminate\Console\Command;
use Symfony\Component\Console\Cursor;

class OldRequestsHistoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'old:requestshistory
                            {--id=* : Идентификаторы заявок}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Перенос истории заявок';

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
        $this->last_id = 0;
        $this->merge = new RequestsMerge;
        $this->cursor = new Cursor($this->output);

        $this->newLine();
        $this->info("Перенос истории заявок");
        $this->line("Начало переноса: " . date("Y-m-d H:i:s"));

        $message = "Подсчет количества строк в таблице истории: ";
        $this->line($message);
        $this->cursor->moveUp();
        $this->cursor->moveRight(mb_strlen($message));

        $count = CrmNewRequestsStory::select('id_request')
            ->where('id_request', '!=', null)
            ->when(count($this->option('id')) > 0, function ($query) {
                $query->whereIn('id_request', $this->option('id'));
            })
            ->orderBy('id_request')
            ->distinct()
            ->count('id_request');

        $this->info($count);

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $stop = false;

        while (!$stop) {

            $stop = $this->handleStep() ? false : true;

            if (!$stop)
                $bar->advance();
        }

        $bar->finish();

        $this->newLine();
        $this->line("Время завершения: " . date("Y-m-d H:i:s"));
        $this->info("Перенос истории заявок завершен");
        $this->newLine();

        return 0;
    }

    /**
     * Выполнение шага переноса заявок
     * 
     * @return boolean
     */
    public function handleStep()
    {
        $crm_request = CrmRequest::select('crm_requests.*')
            ->join('crm_new_requests_story', 'crm_new_requests_story.id_request', '=', 'crm_requests.id')
            ->where('crm_requests.id', '>', $this->last_id)
            ->when(count($this->option('id')) > 0, function ($query) {
                $query->whereIn('crm_requests.id', $this->option('id'));
            })
            ->first();

        if (!$crm_request)
            return false;

        $this->last_id = $crm_request->id;

        CrmNewRequestsStory::where('id_request', $crm_request->id)
            ->get()
            ->map(function ($row) use (&$state, &$change_pin) {

                $new = new RequestsRow;

                /** Идентификатор заявки */
                $new->id = $row->id_request;

                /** Оператор, назначенный на заявку */
                $new->pin = $this->merge->getNewPin($row->pin);

                /** История смены статуса */
                if ($row->state = CrmNewRequestsState::where('idStory', $row->id)->first()) {
                    $new->status_id = $this->merge->getStatusId($row->state);
                    $new->event_at = $row->state->date;
                }

                /** Имя клиента */
                $new->client_name = $row->name != "" ? $row->name : null;

                /** Тематика заявки */
                $new->theme = $row->theme != "" ? $row->theme : null;

                /** Регион нахождения клиента */
                $new->region = ($row->region != "" and $row->region != "Неизвестно")
                    ? $row->region : null;

                /** Проверка региона на принадлежность к Москве */
                $new->check_moscow = $new->region
                    ? RequestChange::checkRegion($new->region) : null;

                /** Комментарий клиента или суть обращения */
                $new->comment = $row->comment != "" ? $row->comment : null;

                /** Комментарий юристу */
                $new->comment_urist = $row->uristComment != "" ? $row->uristComment : null;

                /** Адрес офиса */
                $new->address = $row->address ?: null;

                /** Поднятие в списке заявок (новое обращение с заявкой) */
                if ($row->newRequest) {
                    $new->uplift = 1;
                    $new->uplift_at = $row->create_at;
                }

                /** Время обновления */
                $new->updated_at = $row->create_at;

                /** Идентификатор истории из старой базы данных */
                $new->old_story = $row->id;

                /** Персональный номер сотрудника, делавший изменение */
                $created_pin = $row->pinEdited ? $this->merge->getNewPin($row->pinEdited) : null;

                if ($row->del == 1)
                    $new->deleted_at = $row->create_at;

                if ($row->hided == 1) {
                    $new->uplift = 0;
                }

                /** Создание строки истории */
                $create = [
                    'request_id' => $row->id_request,
                    'row_data' => $new->toArray(),
                    'created' => $row->newRequest,
                    'created_pin' => $created_pin,
                    'created_at' => $row->create_at,
                ];

                $story = RequestsStory::create($create);
                // dump($create);

                if ($row->state) {

                    $create['requests_story_statuses'] = $create_status = [
                        'story_id' => $story->id ?? null,
                        'request_id' => $row->id_request,
                        'status_old' => $state,
                        'status_new' => $new->status_id,
                        'created_pin' => $row->id_request,
                        'created_at' => $row->create_at,
                    ];

                    RequestsStoryStatus::create($create_status);
                    // dump($create_status);
                }

                if ($new->pin != $change_pin) {

                    $create['requests_story_pins'] = $create_pin = [
                        'story_id' => $story->id ?? null,
                        'request_id' => $row->id_request,
                        'old_pin' => $change_pin,
                        'new_pin' => $new->pin,
                        'created_at' => $row->create_at,
                    ];

                    RequestsStoryPin::create($create_pin);
                    // dump($create_pin);
                }

                if ((!$change_pin and $new->pin) or ($new->pin and $change_pin))
                    $change_pin = $new->pin;

                return $create;
            })
            ->toArray();

        $row = new RequestsRow;
        $row->id = $crm_request->id;

        $this->merge->findAndRequestQueries($row);

        return true;
    }
}
