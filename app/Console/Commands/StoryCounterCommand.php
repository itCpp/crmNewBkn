<?php

namespace App\Console\Commands;

use App\Http\Controllers\Ratings\CallCenters;
use App\Http\Controllers\Requests\Counters;
use App\Http\Controllers\Users\DeveloperBot;
use App\Http\Controllers\Users\UserData;
use App\Models\RequestsCounterStory;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

/**
 * Запись истории счетчиков
 * 
 * Данные счетчиков записываются с учетом прав доступа к заявкам, подсчет
 * производится для сотрудников, фигурировавших в рейтинге за прошедший день
 * 
 * Полные данные счетчка записаны с правами супер-админа и сохранены в
 * базе данных без персонального идентификационного номера сотрудника
 * 
 * @method array getPinsFromCallCenterRating(\Illuminate\Http\Request $request)
 * @method \App\Models\RequestsCounterStory writeCounter(\Illuminate\Http\Request $request, string|null $date, bool $to_client)
 */
class StoryCounterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'story:counter
                            {--date= : Дата подсчета}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Записывает историю счетчика';

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
        $request = request();

        if ($date = $this->option('date')) {
            $merge['start'] = $date;
            $merge['stop'] = $date;
        } else {
            $merge['toNextDay'] = true;
        }

        $bot = (new DeveloperBot)();

        $request->merge($merge ?? []);

        $request->setUserResolver(function () use ($bot) {
            return $bot;
        });

        /** Запись общей истории */
        $this->writeCounter($request, $date, true);

        /** Сотрудники, для которых необходимо записать счетчики */
        $pins = $this->getPinsFromCallCenterRating($request);

        foreach ($pins as $pin) {

            if (!$user = User::wherePin($pin)->first())
                continue;

            $request->setUserResolver(function () use ($user) {
                return new UserData($user);
            });

            $this->writeCounter($request, $date);
        }

        return 0;
    }

    /**
     * Выводит список сотрудников, участвующих в ретийнге
     * 
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function getPinsFromCallCenterRating(Request $request)
    {
        $rating = (new CallCenters($request))->get();

        return collect($rating->users ?? [])->map(function ($row) {
            return $row->pin;
        })->toArray();
    }

    /**
     * Подсчет данных и запись истории
     * 
     * @param  \Illuminate\Http\Request $request
     * @param  null|string $date
     * @param  boolean $to_client Флаг подсчета информации о клиентах
     * @return \App\Models\RequestsCounterStory
     */
    public function writeCounter(Request $request, $date, $to_client = false)
    {
        $counters = new Counters;

        $data = $counters->getCounterTabsData($request->user()->getAllTabs());

        if ($to_client)
            $data['clients'] = $counters->getClientsData($date);

        return RequestsCounterStory::create([
            'counter_date' => $date ?: now(),
            'counter_data' => encrypt($data),
            'to_pin' => $request->user()->pin,
        ]);
    }
}
