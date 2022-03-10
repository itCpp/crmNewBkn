<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class DataDump extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:dump
                            {--name= : Имя файла без расширения}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Сохранение данных при переносе ЦРМ';

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
        $models = [
            \App\Models\ChatFile::class,
            \App\Models\ChatMessage::class,
            \App\Models\ChatRoom::class,
            \App\Models\ChatRoomsUser::class,
            \App\Models\ChatRoomsViewTime::class,
            \App\Models\IpInfo::class,
            \App\Models\TestingProcess::class,
            \App\Models\RatingStory::class,
            \App\Models\BlockHost::class,
            \App\Models\BlockIp::class,
        ];

        $this->data = [];

        foreach ($models as $model) {
            $this->dumpModel($model);
        }

        $name = Str::finish($this->option('name') ?: Str::orderedUuid(), '.json');

        $path = storage_path("app/dumps/{$name}");
        (new Filesystem)->ensureDirectoryExists(dirname($path));

        fopen($path, "a+");

        $data = json_encode($this->data, JSON_UNESCAPED_UNICODE);
        file_put_contents($path, $data);

        $this->info("Копия данных для переноса создана:");
        $this->question(Str::replace("\\", "/", $path));
        $this->newLine();

        return 0;
    }

    /**
     * Dump data model
     * 
     * @param string $class
     * @return null
     */
    public function dumpModel($class)
    {
        $data = [
            'model' => $class,
            'rows' => [],
        ];

        try {
            $rows = (new $class)->all();
        } catch (Exception) {
            return null;
        }

        foreach ($rows as $row) {

            foreach ($row->getCasts() as $key => $value) {
                if ($value == "datetime")
                    $datetime[] = $key;
            }

           $dates = array_unique(array_merge($row->getDates(), $datetime ?? []));

           $row_array = $row->toArray();

            foreach ($dates as $key) {
                if ($row->$key and $row->$key instanceof \Illuminate\Support\Carbon) {
                    $row_array[$key] = $row->$key->format("Y-m-d H:i:s");
                }
            }

            $data['rows'][] = $row_array;
        }

        $this->data[] = $data;

        return null;
    }
}
