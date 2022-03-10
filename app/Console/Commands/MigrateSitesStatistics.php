<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\Blocks\OwnStatistics;
use App\Models\Company\AllVisit;
use App\Models\Company\StatVisitSite;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MigrateSitesStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blocks:migrate_stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Перенос статистики посещений на сайтах';

    /**
     * Сопоставление доменов к подключениям
     * Зашифрованный массив доменов для каждого подключения
     * array<int, array>
     * 
     * @var string
     */
    protected $domains = "eyJpdiI6ImtDcmp1SkRSZEJPUWgxUDdqVzN4aUE9PSIsInZhbHVlIjoiSk5FQWl2Nng1eVBmcnJOb1ZvNWVtQVFCcnpEa2VwWFJUVERJVHMycXFVdlBWZ0VCMjhlR1NhVnYzbmRUaXFOY01BUnNtd01OblpiOTZ1aGI1UTloYWxYcEVON2tyeTR6SnEwUElsRFgwWHFIVGpENUVsSUd1WWFpTTNpRUxSU0JNM1JrWXhqZ1Nta3UrUmp2ZVVhN3QvUE5ObnpZRzdHOU43QTdNcWVzRUpVL1FXdWx2eXhjcldUdURXYis4ZXFoKzFRNmJVYnhmVXFKSDFxWlR5R1NmYnF6SG9wcHB0M0NzL3JGNTl5QTdtOERUbG90QWltamM2UGRNU3p0TmxvanRYRnAzVXowZElFT0MzbDJvYXlsb1pkN0JRWmk5VkxmRXA4SjNKWENySCtheFNnZDEvYllqTEthd2Y0N2w5WmFaMkRSQ3B1ZEl1eFNqbTZ6eXc4UnVuWi9BQU50QkZsRGFvd3JWcEV1NGpidHRHM0pJNGhFTEl6aHMrTWc3bDNZSWRGUE5ENUpqcnpMT2VicEY2SUNDZkNIVnZsdGxnY2VnaXFFeXZnSDZaTjJnZUxWUUdDbkdoaG1SMnRTOG1nbnhJeVlRdkYrUU9NQTNWK1hacXA4T2NpTlNvM05JT3pqZitrcEJTeWJhQk5zYjdaRFg2ZTBNa0F1L2tKUXVjTnRwUGpzZE5sNmFwUEYrY2JnNFhsNkpHeXRLMmlpQUhJMlNDeXpYdDZESmdRK1c0WjhPem9lc2IwbXE5clVPb0VRSW00dnJ1WHh1QThxeVE5YUNGTFgzbVExbm85WXNzSmt5eHAwWGZnSjhLMGdFK2dQS1JpU2g5Z1oyK29BVGpCVTVrS05adTI4dTM2WmxNMWJudS9pMWJsNVFoNmIveXNrQm9uRUU4SkZFeUwrY2ZtUElMTWg3WE81YXJ4bW1uWHhaNGhjYmlhdW9IcWpGeit6TUIrSWtwSncrTEN2ZXR6ZmszTUpNU2RKNnNVbDB2b25wNSsxVlFudC9nZDBqOGthM3dzQi9BZTFBVTEzNitQL1dQbTR3VHNLLzF6eHVNSVBucmtKNWNveUU5RWxaZ1QxQzk0RUdMcjExMDVsMWFuTkZGbi9pRW5KelpLcWd1YTFweGxQWUErZUNyRmI3Z2xSWEdRZno0ZkFoT0JoNzhOWXlBaUYwSHRmWFIyVVpkUURWamdUcjdpRjZPMUUwWVJsSjVqdXBSamNUVUVEZllxOWJDeDBhYS9UTTdXaGNVRDkvTkExSThWbHVlZ25aM1Jyamp0OUZCZytWakplekp3b3ZlY2dwSmNVWUkycTdFT2k4OSsweVI0QmE1cjEzbW5PRlU1eEVqUkNhTnlRa3B6eE1yTTdZcFJMZWdpeG4vZERjUWNZQW9ZbW5BaTVKTXUwVENIQXQwL01mdnRrUE9Sc2RmNWlBTGdQNm1vaUdBczUiLCJtYWMiOiIyNDA5YWY4ZjNmMTcwZDAyY2MzMzg3NjAxYWZkNDk2MWYwNzA5ODBmZDU3OGE4NmJkNzA3ZDVmNzIwNTdhMzg4IiwidGFnIjoiIn0=";

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
        $this->own = new OwnStatistics(new Request);

        $this->domains = decrypt($this->domains);

        foreach ($this->own->connections() as $connection) {

            $id = config("database.connections.{$connection}.connection_id");

            if ($this->domains[$id] ?? null) {
                $this->handleStep($connection, $this->domains[$id]);
            }
        }

        return 0;
    }

    /**
     * One step migrate statistics
     * 
     * @param string $connection
     * @param array $sites
     * @return null
     */
    public function handleStep(string $connection, array $sites)
    {
        $this->question("   {$connection}   ");

        if ($count = AllVisit::whereIn('site', $sites)->count()) {

            $bar = $this->output->createProgressBar($count);

            $this->info('Перенос истории посещений');
            $bar->start();

            $stop = true;
            $this->id = 0;

            while ($stop) {
                $stop = $this->writeVisits($connection, $sites);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        } else {
            $this->error(" Посещений не найдено ");
        }

        if ($count = StatVisitSite::whereIn('site', $sites)->count()) {

            $bar = $this->output->createProgressBar($count);

            $this->info('Перенос истории счетчиков');
            $bar->start();

            $stop = true;
            $this->id = 0;

            while ($stop) {
                $stop = $this->writeStory($connection, $sites);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        } else {
            $this->error(" Исчтории счетчиков не найдено ");
        }

        $this->newLine();

        return null;
    }

    /**
     * Запись истории посещений
     * 
     * @param string $connection
     * @param array $sites
     * @return bool
     */
    public function writeVisits(string $connection, array $sites): bool
    {
        if (!$row = AllVisit::whereIn('site', $sites)->where('id', '>', $this->id)->first())
            return false;

        $this->id = $row->id;

        try {
            DB::connection($connection)
                ->table('visits')
                ->insert([
                    'ip' => $row->ip,
                    'page' => $row->page,
                    'referer' => $row->referer,
                    'user_agent' => $row->user_agent,
                    'request_data' => json_encode($row->other_data ?: [], JSON_UNESCAPED_UNICODE),
                    'created_at' => $row->created_at,
                ]);
        } catch (Exception) {
        }

        return true;
    }

    /**
     * Запись истории
     * 
     * @param string $connection
     * @param array $sites
     * @return bool
     */
    public function writeStory(string $connection, array $sites): bool
    {
        if (!$row = StatVisitSite::whereIn('site', $sites)->where('id', '>', $this->id)->first())
            return false;

        $this->id = $row->id;

        try {
            DB::connection($connection)
                ->table('statistics')
                ->insert([
                    'date' => $row->date,
                    'ip' => $row->ip,
                    'hostname' => gethostbyaddr($row->ip),
                    'visits' => (int) $row->count,
                    'requests' => (int) $row->requests,
                    'visits_drops' => (int) $row->count_block,
                ]);
        } catch (Exception) {
        }

        return true;
    }
}
