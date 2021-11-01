<?php

namespace Database\Seeders;

use App\Models\RequestsSource;
use Illuminate\Database\Seeder;

class RequestsSourceSeeder extends Seeder
{
    /**
     * Колонки данных
     * 
     * @var array
     */
    protected $rowsColumn = [
        'id', 'name', 'comment', 'show_counter', 'actual_list', 'auto_done_text_queue'
    ];

    /**
     * Данные
     * 
     * @var array
     */
    protected $rowsData = [
        [1, 'Юридическая консультация', NULL, 1, 1, 0],
        [2, 'БАСМАНКА', NULL, 1, 1, 0],
        [3, 'СПР', 'Союз Профсоюзов России', 0, 0, 0],
        [4, 'ДОСТ', NULL, 0, 0, 0],
        [5, 'ХУД', 'Личный номер Худякова', 0, 0, 0],
        [6, 'БРАКИ', 'Заявки, относящиеся к несуществующим сайтам и номерам', 0, 0, 0],
        [7, 'Эксперты права', NULL, 1, 0, 0],
        [8, 'Московская коллегия адвокатов', NULL, 1, 1, 1],
        [9, 'Правовые эксперты России', NULL, 1, 0, 1],
        [10, 'Коллегия адвокатов', NULL, 1, 0, 0],
        [11, 'Департамент юридических услуг', NULL, 1, 1, 0],
        [12, 'ЦПП', NULL, 0, 0, 0],
        [14, 'БЗБ', NULL, 0, 0, 0],
        [15, 'ГАЗЕТА', NULL, 0, 0, 0],
        [16, 'КОНСАЛТИНГ', NULL, 0, 0, 0],
        [17, 'Подарки от Худякова', NULL, 0, 0, 0],
        [18, 'ЮК', NULL, 0, 0, 0],
        [19, 'Юридический центр', NULL, 0, 0, 0],
        [20, 'ЮРИСКОНСУЛЬТ', NULL, 0, 0, 0],
        [21, 'ЮРСЛУЖБА', NULL, 0, 0, 0],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->rowsData as $row) {
            CreateCrm::createRow($row, $this->rowsColumn, RequestsSource::class);
        }

        $this->call([
            RequestsSourcesResourceSeeder::class,
        ]);
    }
}
