<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class DataRestore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:restore
                            {--name= : Имя файла с данными}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Восстановление данных при переносе ЦРМ';

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
        $this->info("\n\nВосстановление сохраненных данных:\n");

        $name = Str::finish($this->option('name') ?: Str::orderedUuid(), '.json');
        $path = storage_path("app/dumps/{$name}");

        if (!(new Filesystem)->exists($path)) {
            $this->info("Файл $name для восстановления данных отсутствует");
            return 0;
        }

        $data = file_get_contents($path);

        if (!$data = json_decode($data, true)) {
            $this->error("Ошибка декодирования json в файле сохраненных данных");
            return 0;
        }

        foreach ($data as $row) {
            $this->restoreModelData($row);
        }

        $this->info("Данные восстановлены");

        return 0;
    }

    /**
     * Восстановление данных можели
     * 
     * @param array
     * @return null
     */
    public function restoreModelData($data)
    {
        $class = $data['model'];
        $count = 0;

        foreach ($data['rows'] ?? [] as $row) {
            try {
                (new $class)->create($row);
                $count++;
            } catch (Exception $e) {
                $this->line("\t{$class}::class <error>{$e->getMessage()}</>");
            }
        }

        $this->line("\t{$class}::class <info>Восстановлено строк:</info> <question> {$count} </question>");

        return null;
    }
}
