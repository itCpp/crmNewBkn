<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    /**
     * Колонки данных
     * 
     * @var array
     */
    protected $rowsColumn = [
        'id', 'base_id', 'active', 'name', 'addr', 'address'
    ];

    /**
     * Данные
     * 
     * @var array
     */
    protected $rowsData = [
        [1, '', 0, 'Волконский', '1-й Волконский пер., д. 15', 'г. Москва, 1-й Волконский пер., д. 15'],
        [2, 'ЮРИСКОНСУЛЬТ', 0, 'Таганский', 'ул. Таганская, д. 15, стр. 2', 'г. Москва, ул. Таганская, д. 15, стр. 2'],
        [3, 'ЮРСЛУЖБА', 0, 'Хамовники', '2-й Обыденский пер., д. 12', 'г. Москва, 2-й Обыденский пер., д. 12'],
        [4, 'БАУ', 1, 'Басманский', 'Бауманская ул., 43/1с1', '105005, г. Москва, Бауманская улица, 43/1с1'],
        [5, 'СПР', 0, 'СПР', 'Посланников пер., д. 1', '105005, г. Москва, Посланников пер., д. 1'],
        [6, 'МАЯ', 0, 'Маяковка', 'ул. Малая Дмитровка, д. 24/2', 'г. Москва, ул. Малая Дмитровка, д. 24/2, 1 этаж'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->rowsData as $row) {
            CreateCrm::createRow($row, $this->rowsColumn, Office::class);
        }
    }
}
