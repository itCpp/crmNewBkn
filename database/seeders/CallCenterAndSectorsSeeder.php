<?php

namespace Database\Seeders;

use App\Models\Callcenter;
use App\Models\CallcenterSector;
use Illuminate\Database\Seeder;

class CallCenterAndSectorsSeeder extends Seeder
{
    /**
     * Список колл-центров
     * 
     * @var array
     */
    protected $callcenters = [
        ['id' => 1, 'name' => 'Москва', 'comment' => NULL, 'active' => 0],
        ['id' => 2, 'name' => 'Саратов', 'comment' => NULL, 'active' => 1],
        ['id' => 3, 'name' => 'Набережные Челны', 'comment' => NULL, 'active' => 0],
    ];

    /**
     * Список секторов
     * 
     * @var array
     */
    protected $callcenter_sectors = [
        ['id' => 1, 'callcenter_id' => 1, 'name' => 'М', 'comment' => NULL, 'active' => 0],
        ['id' => 2, 'callcenter_id' => 2, 'name' => 'А', 'comment' => NULL, 'active' => 1],
        ['id' => 3, 'callcenter_id' => 3, 'name' => 'CH1', 'comment' => NULL, 'active' => 0]
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->callcenters as $row)
            Callcenter::create($row);

        foreach ($this->callcenter_sectors as $row)
            CallcenterSector::create($row);
    }
}
