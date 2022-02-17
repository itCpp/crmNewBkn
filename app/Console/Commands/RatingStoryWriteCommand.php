<?php

namespace App\Console\Commands;

use App\Http\Controllers\Dates;
use App\Http\Controllers\Ratings\CallCenters;
use App\Http\Controllers\Users\DeveloperBot;
use App\Models\RatingStory;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class RatingStoryWriteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rating:write';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Запись истории рейтинга';

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
        $this->question('Запись истории рейтинга');
        
        $this->dates = new Dates();

        $request = request();

        $request->setUserResolver(function () {
            return (new DeveloperBot)();
        });

        $request->start = $this->dates->start;
        $request->stop = $this->dates->stop;

        $rating = (new CallCenters($request))->get();
        $write = [];

        $to_old = env("NEW_CRM_OFF", true);

        foreach ($rating->users as $user) {
            $write[] = RatingStory::create([
                'to_day' => $request->start,
                'pin' => $to_old ? ($user->pinOld ?: $user->pin) : $user->pin,
                'rating_data' => $user,
            ]);
        }

        $this->info("История за один день ({$request->start}) записана, создано строк: " . count($write));

        if ($rating->dates->stop != $rating->dates->stopPeriod)
            return 0;

        $request->start = $this->dates->startPeriod;
        $request->stop = $this->dates->stopPeriod;
        $request->toPeriod = true;

        $rating = (new CallCenters($request))->get();
        $write = [];

        foreach ($rating->users as $user) {
            $write[] = RatingStory::create([
                'to_period' => $request->start,
                'pin' => $to_old ? ($user->pinOld ?: $user->pin) : $user->pin,
                'rating_data' => $user,
            ]);
        }

        $this->info("История за период ({$request->start}) записана, создано строк: " . count($write));

        return 0;
    }
}
