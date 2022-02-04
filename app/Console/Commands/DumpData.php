<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class DumpData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dump:data
                {--name= : Имя файла без расширения}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump data before refresh CRM';

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
            \App\Models\ChatRoom::class,
            \App\Models\ChatRoomsUser::class,
            \App\Models\ChatRoomsViewTime::class,
            \App\Models\TestingProcess::class,
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

        foreach ((new $class)->all() as $row) {
            $data['rows'][] = $row->toArray();
        }

        $this->data[] = $data;

        return null;
    }
}
