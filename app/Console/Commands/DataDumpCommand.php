<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class DataDumpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:dump
                            {--name= : Имя каталога сохранения файлов}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Сохранение данных при переносе ЦРМ';

    /**
     * Модели, сохраняемые в бэкапе
     * 
     * @var array
     */
    protected $models = [
        \App\Models\ChatFile::class,
        \App\Models\ChatMessage::class,
        \App\Models\ChatRoom::class,
        \App\Models\ChatRoomsUser::class,
        \App\Models\ChatRoomsViewTime::class,
        \App\Models\IpInfo::class,
        // \App\Models\TestingProcess::class,
        \App\Models\RatingPeriodStory::class,
        \App\Models\RatingGlobalData::class,
        \App\Models\RatingStory::class,
        \App\Models\BlockHost::class,
        \App\Models\BlockIp::class,
        \App\Models\SmsMessage::class,
        \App\Models\UsersViewPart::class,
        \App\Models\Expense::class,
        \App\Models\ExpensesAccount::class,
    ];

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
        $this->info("Создание копии данных для переноса:");

        $this->dir = $this->option('name') ?: Str::orderedUuid();

        $this->data = [];

        foreach ($this->models as $model) {
            $this->dumpModel($model);
        }

        if (!count($this->data)) {
            $this->warn("Данных для переноса нет");
            return 0;
        }

        $model = "";

        foreach ($this->data as $key => $row) {

            if ($row['model'] != $model) {
                $model = $row['model'];
                $this->question(" {$row['model']} ");
            }

            $name = Str::finish(Str::orderedUuid(), '.json');
            $name = Str::finish(Str::padLeft($key, 6, "0") . "_" . md5($model), '.json');
            $path = storage_path("app/dumps/{$this->dir}/{$name}");

            (new Filesystem)->ensureDirectoryExists(dirname($path));

            fopen($path, "a+");

            $data = json_encode($row, JSON_UNESCAPED_UNICODE);
            file_put_contents($path, $data);

            $this->info(Str::replace("\\", "/", $path));
        }

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
        try {
            (new $class)->reorder()->chunk(100, function ($rows) use ($class) {
                $this->forEachRows($rows, $class);
            });
        } catch (Exception $e) {
            $this->error(" {$class} ");
            $this->line("<fg=red>" . $e->getMessage() . "</>");
        }

        return null;
    }

    /**
     * Обходит строки и сохраняет данные в общий массив
     * 
     * @param  array $rows
     * @param  string $class
     * @return null
     */
    public function forEachRows($rows, $class)
    {
        $data = [
            'model' => $class,
            'rows' => [],
        ];

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
