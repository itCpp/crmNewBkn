<?php

namespace Database\Seeders;

use App\Models\SettingsGlobal;
use Illuminate\Database\Seeder;

class SettingsGlobalSeeder extends Seeder
{
    /**
     * Массив данных
     * 
     * @var array
     */
    protected $settings = [
        [
            'id' => "TEXT_REQUEST_AUTO_ADD",
            'value' => 0,
            'type' => null,
            'comment' => "Автоматически добавлять текстовую заявку минуя очередь",
        ],
        [
            'id' => "DROP_ADD_REQUEST",
            'value' => 1,
            'type' => null,
            'comment' => "Отклонять запросы на добавление новых заявок",
        ]
    ];
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->settings as $row) {
            SettingsGlobal::create($row);
        }
    }
}
