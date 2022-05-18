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
        ],
        [
            'id' => "CRONTAB_SMS_INCOMINGS_CHECK",
            'value' => 0,
            'type' => null,
            'comment' => "Проверка входящих смс на шлюзах",
        ],
        [
            'id' => "CALL_DETAIL_RECORDS_SAVE",
            'value' => 0,
            'type' => null,
            'comment' => "Сохранять информацию о звонках",
        ],
        [
            'id' => "ASTERISK_INCOMING_CALL_TO_CREATE_REQUESTS",
            'value' => 0,
            'type' => null,
            'comment' => "Добавлять заявки от входящих звонков Asterisk",
        ],
        [
            'id' => "AUTOSET_SECTOR_NEW_REQUEST",
            'value' => 2,
            'type' => "integer",
            'comment' => "Идентификатор сектора для автоматической установки новой заявке",
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
