<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\ConsoleOutput;

class CreateCrm extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            MoscowCitySeeder::class, // Список городов Московской области
            PermissionSeeder::class, // Начальный список разрешений
            RolesSeeder::class, // Начальные роли
            SettingsGlobalSeeder::class, // Глобальные настройки ЦРМ
            IncomingCallsToSourceSeeder::class, // Источники слушателей входящих звонков
            OfficeSeeder::class, // Список офисов
            RequestsSourceSeeder::class, // Источники
            StatusSeeder::class, // Статусы заявок
            TabSeeder::class, // Вкладки заявок
            CallCenterAndSectorsSeeder::class, // Создание колл-центров
        ]);

        $output = new ConsoleOutput; // Вывод в консоль

        Artisan::call('old:users', [], $output); // Перенос сотрудников
        Artisan::call('old:requests', [], $output); // Перенос старых заявок
    }

    /**
     * Создание строки из массива колонок и данных
     * 
     * @param array $row Массив данных
     * @param array $columns Массив с наименованием колонок
     * @param mixed $model Класс модели
     * @return mixed
     */
    public static function createRow($row, $column, $model)
    {
        $new = new $model;
        $create = [];

        foreach ($row as $key => $value) {
            $create[$column[$key]] = $value;
        }

        return $new->create($create);
    }
}
