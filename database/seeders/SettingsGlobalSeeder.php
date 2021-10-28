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
    protected $rows = [
        [
            'name' => "TEXT_REQUEST_AUTO_ADD",
            'value' => 0,
            'type' => null,
            'comment' => "Автоматически добавлять текстовую заявку минуя очередь",
        ]
    ];
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->rows as $row) {
            SettingsGlobal::create($row);
        }
    }
}
