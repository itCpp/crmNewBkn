<?php

namespace App\Console\Commands;

use App\Models\Office;
use App\Models\RequestsRow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RequestsExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'requests:export
                            {--start= : Дата начала периода}
                            {--stop= : Дата окончания периода}
                            {--city=Москва : Город}
                            {--theme= : Тематика}
                            {--filename= : Имя файла вывода}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Экспорт заявок';

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
        $start = $this->option('start');
        $stop = $this->option('stop');

        if (!$start || !$stop) {
            $this->error('Необходимо указать даты начала и окончания периода (флаг --stop= и --start=)');
            return 1;
        }

        $requests = $this->exportData($start, $stop, $this->option('city'), $this->option('theme'));

        $path = $this->option('filename') ?: "requests/export/"
            . now()->format("YmdHis")
            . "-exportleads-"
            . now()->create($start)->format("Ymd")
            . "-"
            . now()->create($stop)->format("Ymd")
            . ".txt";

        $string = ($first = $requests->first())
            ? collect(array_keys($first))->implode("|") . "\n"
            : "";
        $string .= $requests->map(fn ($item) => collect($item)->implode("|"))->implode("\n");

        $storage = Storage::disk('local');
        $storage->put($path, $string);

        $this->info("Найдено заявок: " . count($requests));
        $this->info("Сохранено в файл " . $storage->path($path));

        return 0;
    }

    /**
     * Формирование массива с данными
     * 
     * @param  string  $start
     * @param  string  $stop
     * @param  string|null  $city
     * @param  string|null  $theme
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function exportData($start, $stop, $city, $theme)
    {
        return RequestsRow::query()
            ->whereBetween('created_at', [
                now()->create($start)->startOfDay()->format("Y-m-d H:i:s"),
                now()->create($stop)->endOfDay()->format("Y-m-d H:i:s"),
            ])
            ->when($city == "Москва" || empty($city), function ($query) {
                $query->where(function ($query) {
                    $query->where('check_moscow', 1)
                        ->orWhere('check_moscow', null);
                });
            })
            ->when(!empty($city) && $city != "Москва", function ($query) use ($city) {

                $query->where(function ($query) use ($city) {

                    collect(explode(",", $city))
                        ->map(fn ($item) => trim($item))
                        ->filter(fn ($item) => !empty($item))
                        ->values()
                        ->each(function ($item) use ($query) {

                            if ($item == "Москва") {
                                $query->orWhere(function ($query) {
                                    $query->where('check_moscow', 1)
                                        ->orWhere('check_moscow', null);
                                });
                            } else {
                                $query->orWhere('region', $item);
                            }
                        });
                });
            })
            ->when(!empty($theme), function ($query) use ($theme) {
                $query->whereIn('theme', collect(explode(",", $theme))
                    ->map(fn ($item) => trim($item))
                    ->filter(fn ($item) => !empty($item))
                    ->values()
                    ->toArray());
            })
            ->where('status_id', '!=', 7)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'status' => $item->status->name ?? null,
                    'source' => $item->source->name ?? null,
                    'date' => $item->created_at ? $item->created_at->format("Y-m-d H:i") : $item->created_at,
                    'uplift' => $item->uplift_at ? $item->uplift_at->format("Y-m-d H:i") : $item->uplift_at,
                    'pin' => $item->pin,
                    'client' => $item->client_name,
                    'phones' => collect($item->clients)
                        ->map(fn ($client) => decrypt($client->phone, false))
                        ->implode(","),
                    'region' => $item->region,
                    'theme' => $item->theme,
                    'address' => $item->office->name ?? null,
                    'comment' => $item->comment,
                ];
            });
    }
}
