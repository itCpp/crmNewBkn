<?php

namespace Database\Seeders;

use App\Models\IncomingCallsToSource;
use Illuminate\Database\Seeder;

class IncomingCallsToSourceSeeder extends Seeder
{
    /**
     * Колонки данных
     * 
     * @var array
     */
    protected $rowsColumn = [
        'id', 'extension', 'phone', 'on_work', 'ad_place', 'comment', 'for_pin'
    ];

    /**
     * Данные
     * 
     * @var array
     */
    protected $rowsData = [
        [14, '78005000380@mango', '78005000380', 1, NULL, 'СПР Манго-офис', NULL],
        [13, '78005001573@mango', '78005001573', 1, NULL, 'цпп.рф', NULL],
        [12, '78005002489@mango', '78005002489', 1, NULL, 'цпп', NULL],
        [11, '78003333404@mango', '78003333404', 1, NULL, 'цпп', NULL],
        [10, '904@192.168.0.15', '74952331264', 1, NULL, 'Басманка', NULL],
        [9, 'sip:74951233027', '74951233027', 1, NULL, 'цпп.рф', NULL],
        [8, 'sip:74951978120', '74951978120', 1, NULL, 'yuris-konsult.ru', NULL],
        [7, 'sip:74951978661', '74951978661', 1, 'yandex', 'gosyurist Я', NULL],
        [6, 'sip:74957270968', '74951233027', 1, 'google', 'цпп.рф', NULL],
        [5, 'sip:74995509611', '74995509611', 1, 'yandex', 'Маяк Я', NULL],
        [4, 'sip:74996733020', '74996733020', 0, NULL, 'yur-experts.ru', NULL],
        [3, 'sip:74951980812', '74951980812', 1, NULL, 'правовые эксперты', NULL],
        [2, 'sip:74952212706', '74952212706', 1, NULL, 'civil-right', NULL],
        [1, 'sip:74996732340', '74996732340', 1, NULL, 'civil-right', NULL],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->rowsData as $row) {
            CreateCrm::createRow($row, $this->rowsColumn, IncomingCallsToSource::class);
        }
    }
}
