<?php

namespace Database\Seeders;

use App\Http\Controllers\Settings;
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
            SettingsGlobalSeeder::class, // Глобальные настройки ЦРМ
            OfficeSeeder::class, // Список офисов
            PermissionSeeder::class, // Начальный список разрешений
            StatusSeeder::class, // Статусы заявок
            TabSeeder::class, // Вкладки заявок
            CallCenterAndSectorsSeeder::class, // Создание колл-центров
            RolesSeeder::class, // Начальные роли
            RequestsSourceSeeder::class, // Источники
            IncomingCallsToSourceSeeder::class, // Источники слушателей входящих звонков
            MoscowCitySeeder::class, // Список городов Московской области
            SettingsQueuesDatabasesSeeder::class, // Настройки внешних бд для очередей
        ]);

        $output = new ConsoleOutput; // Вывод в консоль

        Artisan::call('old:users', [], $output); // Перенос сотрудников
        Artisan::call('old:requests', [], $output); // Перенос старых заявок

        Settings::set('DROP_ADD_REQUEST', false); // Отключение блокировки добавления новых заявок
        Settings::set('CRONTAB_SMS_INCOMINGS_CHECK', true); // Включение проверки СМС на шлюзах
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
