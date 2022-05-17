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
                            {--name= : Имя каталога с файлами}';

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
        $this->info("Восстановление сохраненных данных:");

        if (!$this->option('name')) {
            $this->error(" Наименование каталога с файлами не передано параметром --name= ");
            return 0;
        }

        $dir = $this->option('name');
        $path = storage_path("app/dumps/{$dir}");

        $filesystem = new Filesystem;

        if (!(new Filesystem)->exists($path)) {
            $this->error(" Каталог $dir с файлами восстановления данных отсутствует ");
            return 0;
        }

        $files = $filesystem->allFiles($path);

        if (!count($files)) {
            $this->error(" Файлов для восстановления не найдено ");
            return 0;
        }

        foreach ($files as $file) {
            $this->readFilesAndRestoreDataFile($file);
        }

        $name = Str::finish($this->option('name') ?: Str::orderedUuid(), '.json');

        if (!(new Filesystem)->exists($path)) {
            $this->info("Файл $name для восстановления данных отсутствует");
            return 0;
        }

        $this->info("Данные восстановлены");

        return 0;
    }

    /**
     * Читает содержимое файла
     * 
     * @param  \Symfony\Component\Finder\SplFileInfo $file
     * @return null
     */
    public function readFilesAndRestoreDataFile($file)
    {
        $this->info($file->getFilename());

        try {

            $data = $file->getContents();

            if (!$data = json_decode($data, true)) {
                $this->error(" Ошибка декодирования json в файле сохраненных данных ");
                return null;
            }

            $this->restoreModelData($data);
        } catch (Exception $e) {
            $this->error(" {$e->getMessage()} ");
        }

        return null;
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
                $this->line("\t{$class} <error>{$e->getMessage()}</>");
            }
        }

        $this->line("{$class} <info>Восстановлено строк:</info> <question> {$count} </question>");

        return null;
    }
}
