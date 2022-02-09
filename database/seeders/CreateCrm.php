<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

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
            TestingQuestionSeeder::class, // Вопросы тестирования
            UsersPositionSeeder::class, // Создание должностей
            GatesSeeder::class, // Создание шлюзов
        ]);
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
