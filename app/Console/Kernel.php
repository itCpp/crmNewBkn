<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Storage;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        $path = "logs/cron/result/" . date("Y/m/d");
        $file_name = date("Ymd_H");

        $disk = Storage::disk('storage');

        if (!$disk->exists($path))
            $disk->makeDirectory($path);

        $path = storage_path($path);

        /** Проверка входящих СМС на шлюзах */
        $schedule->command('sms:incomings')
            ->everyMinute()
            ->between('8:30', '22:00')
            ->appendOutputTo("{$path}/{$file_name}_sms_incomings.log")
            ->runInBackground();

        /** Автоматическая смена статуса заявок */
        $schedule->command('requests:autochange')
            ->everyMinute()
            ->between('9:00', '20:00')
            ->appendOutputTo("{$path}/{$file_name}_requests_autochange.log")
            ->runInBackground();

        /** Проверка очереди заявок */
        $schedule->command('requests:getfromsite', ['--while', '--sleep' => 10])
            ->everyMinute()
            ->appendOutputTo("{$path}/{$file_name}_requests_getfromsite.log")
            ->runInBackground();

        /** Перешифровка данных событий с использованием внутреннего ключа шифрования */
        $schedule->command('events:recrypt')
            ->everyMinute()
            ->appendOutputTo("{$path}/{$file_name}_events_recrypt.log")
            ->runInBackground();

        /** Завершает все активные сессии */
        $schedule->command('users:endsessions')
            ->dailyAt("20:30")
            ->appendOutputTo("{$path}/{$file_name}_users_endsessions.log")
            ->runInBackground();

        /** Запись истории счетчика заявок */
        $schedule->command('story:counter')
            ->dailyAt("23:59")
            ->appendOutputTo("{$path}/{$file_name}_story_counter.log")
            ->runInBackground();

        /** Запись истории рейтинга колл-центра */
        $schedule->command('rating:write')
            ->dailyAt("23:59")
            ->appendOutputTo("{$path}/{$file_name}_rating_write.log")
            ->runInBackground();

        /** Планировщик проверки сайтов */
        $schedule->command('sites:check')
            ->everyFiveMinutes()
            ->between('8:00', '21:00')
            ->appendOutputTo("{$path}/{$file_name}_sites_check.log")
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
