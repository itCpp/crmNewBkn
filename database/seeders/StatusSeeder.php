<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Колонки данных
     * 
     * @var array
     */
    protected $rowsColumn = [
        'id', 'name', 'theme', 'zeroing', 'event_time', 'zeroing_data',
    ];

    /**
     * Данные
     * 
     * @var array
     */
    protected $rowsData = [
        [1, 'Недозвон', 5, 1, 0, '{"algorithm": "xHour", "time_created": 1, "time_updated": 1, "algorithm_option": "1"}'],
        [2, 'Созвон', 3, 1, 1, '{"algorithm": "nextDay", "time_event": 1, "time_created": 1, "time_updated": 1}'],
        [3, 'Запись', 1, 0, 1, NULL],
        [4, 'Подтверждено', 1, 0, 1, NULL],
        [5, 'БК', 6, 1, 0, '{"algorithm": "nextDay", "time_created": 1, "time_updated": 1}'],
        [6, 'Брак', 4, 1, 0, '{"algorithm": "nextDay", "time_created": 1, "time_updated": 1}'],
        [7, 'Приход', 7, 0, 1, NULL],
        [8, 'Слив', 2, 1, 0, '{"algorithm": "xDays", "time_created": 1, "time_updated": 1, "algorithm_option": "7"}'],
        [9, 'Промо', NULL, 0, 0, NULL],
        [10, 'Онлайн', 8, 0, 0, '{"algorithm": "nextDay","time_created": 1}'],
        [11, 'Договор', NULL, 0, 0, NULL],
        [12, 'Промо с цветом', NULL, 0, 0, NULL],
        [13, 'Вторичка', 11, 0, 0, NULL],
        [14, 'Доставка подарка', NULL, 0, 0, NULL],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->rowsData as $row) {
            CreateCrm::createRow($row, $this->rowsColumn, Status::class);
        }
    }
}
