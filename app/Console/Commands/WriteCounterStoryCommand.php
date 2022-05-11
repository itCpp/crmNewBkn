<?php

namespace App\Console\Commands;

use App\Http\Controllers\Requests\Counters;
use App\Http\Controllers\Users\DeveloperBot;
use App\Models\RequestsCounterStory;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class WriteCounterStoryCommand extends Command
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

        $request->merge($merge ?? []);

        $request->setUserResolver(function () {
            return (new DeveloperBot)();
        });

        $counters = (new Counters)->getCounterTabsData($request->user()->getAllTabs());

        RequestsCounterStory::create([
            'counter_date' => $date ?: now(),
            'counter_data' => encrypt($counters),
        ]);

        return 0;
    }
}
