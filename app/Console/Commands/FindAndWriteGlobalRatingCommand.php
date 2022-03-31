<?php

namespace App\Console\Commands;

use App\Http\Controllers\Dates;
use App\Http\Controllers\Ratings\CallCenters;
use App\Http\Controllers\Users\DeveloperBot;
use App\Models\RatingGlobalData;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FindAndWriteGlobalRatingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'writerating
                            {--start= : Дата начала проверки}
                            {--stop= : Дата окончания проверки}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Находит и записывает все данные по рейтингу в глобальную таблицу';

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
        $start = $this->option('start') ?: now()->format("Y-m-d");

        if (!strtotime($start)) {
            return $this->error("Неправильная дата [--start=<bg=red;options=bold>{$start}</>]");
        }

        $stop = $this->option('stop') ?: now()->format("Y-m-d");

        if (!strtotime($stop)) {
            return $this->error("Неправильная дата [--stop=<bg=red;options=bold>{$stop}</>]");
        }

        $this->start = Carbon::create($start)->format("Y-m-d");
        $this->stop = Carbon::create($stop)->format("Y-m-d");

        $this->info("Запись глобального рейтинга с {$this->start} по {$this->stop}");
        $this->line("Для каждого сотрудника будет создана строка с глобальной статистикой");
        $this->line("Для исключения наложения друг на друга статистических данных, подсчет будет остановлен с даты создания строки статистики сотрудника");

        $this->newLine();

        $this->dates = new Dates($this->start, $this->start);

        $process = true;

        while ($process) {
            $process = $this->handlerStep($this->dates->startPeriod, $this->dates->stopPeriod);
        }

        if (count($this->update_created_at ?? [])) {

            $time = strtotime($this->start);
            $format = ((int) date("d", $time) >= 16) ? "Y-m-t" : "Y-m-15";
            $created_at = Carbon::create(date($format, $time))->addDay()->subMinutes(5);

            RatingGlobalData::whereIn('id', $this->update_created_at)
                ->update(['created_at' => $created_at]);

            $this->line("Обновление даты: " . implode(", ", $this->update_created_at));
        }

        return 0;
    }

    /**
     * Получение рейтинга за указанный период
     * 
     * @param string $start
     * @param string $stop
     * @return bool
     */
    public function handlerStep($start, $stop)
    {
        $this->info("Рейтинг за период {$start}");

        $request = request();

        $request->setUserResolver(function () {
            return (new DeveloperBot)();
        });

        $request->start = $start;
        $request->stop = $stop;

        $rating = (new CallCenters($request))->get();

        $count = 0;
        $stop .= " 23:59:59";

        foreach ($rating->users ?? [] as $user) {

            $row = RatingGlobalData::firstOrNew([
                'pin' => $user->pin,
            ]);

            if (!$row->created_at)
                $row->created_at = null;

            if ($row->created_at and $row->created_at < $stop)
                continue;

            $row->requests += ($user->requestsAll ?? 0);
            $row->requests_moscow += ($user->requests ?? 0);
            $row->comings += ($user->comings ?? 0);
            $row->drains += ($user->drains ?? 0);
            $row->agreements_firsts += ($user->agreements['firsts'] ?? 0);
            $row->agreements_seconds += ($user->agreements['seconds'] ?? 0);
            $row->cashbox += ($user->cahsbox ?? 0);

            $row->save();

            if (!$row->created_at or ($row->created_at and $row->created_at > $stop)) {
                $this->update_created_at[] = $row->id;
            }

            $count++;
        }

        $this->line("Записано строк: <fg=green;options=bold>{$count}</>");

        $this->dates = new Dates($start, $stop, "periodNext");

        if ($this->dates->stopPeriod > $this->stop)
            return false;

        return true;
    }
}
