<?php

namespace Database\Seeders;

use App\Models\RequestsSourcesResource;
use Illuminate\Database\Seeder;

class RequestsSourcesResourceSeeder extends Seeder
{
    /**
     * Колонки данных
     * 
     * @var array
     */
    protected $rowsColumn = [
        'id', 'source_id', 'type', 'val',
    ];

    /**
     * Данные
     * 
     * @var array
     */
    protected $rowsData = [
        [1, 1, 'site', 'gosyurist.ru'],
        [2, 1, 'site', 'ros-yuristy.ru'],
        [3, 1, 'phone', '74951978661'],
        [4, 1, 'phone', '78005002096'],
        [5, 1, 'phone', '78005002489'],
        [6, 2, 'site', 'yuris-konsult.ru'],
        [7, 2, 'phone', '74951978120'],
        [8, 2, 'phone', '74952331264'],
        [9, 2, 'phone', '78002503210'],
        [10, 2, 'phone', '78003333404'],
        [11, 3, 'site', 'xn--g1acavabdidhea6a9gxb.xn--p1ai'],
        [12, 3, 'phone', '78005000380'],
        [13, 3, 'site', 'профсоюзыроссии.рф'],
        [14, 4, 'site', 'dostoinaya-zhizn.ru'],
        [15, 5, 'site', 'hudyakovroman.ru'],
        [16, 5, 'phone', '79167324970'],
        [17, 7, 'site', 'xn----8sbahm3a9achcfp1jva.xn--p1ai'],
        [18, 7, 'phone', '74995509611'],
        [19, 7, 'site', 'm.эксперты-права.рф'],
        [20, 7, 'site', 'эксперты-права.рф'],
        [21, 10, 'site', 'xn----8sbf5ajmeav8b.xn--p1ai'],
        [22, 10, 'site', 'xn--o1aat.xn--p1ai'],
        [23, 10, 'phone', '74951233027'],
        [24, 10, 'phone', '74952270665'],
        [25, 10, 'phone', '78005001573'],
        [26, 10, 'site', 'цпп-москва.рф'],
        [27, 10, 'site', 'цпп.рф'],
        [28, 9, 'site', 'yur-experts.ru'],
        [29, 9, 'phone', '74951980812'],
        [30, 8, 'site', 'civil-right.ru'],
        [31, 8, 'phone', '74952212706'],
        [32, 8, 'phone', '74996732340'],
        [33, 11, 'site', 'yurcentre.ru'],
        [34, 11, 'phone', '74996733020'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->rowsData as $row) {
            CreateCrm::createRow($row, $this->rowsColumn, RequestsSourcesResource::class);
        }
    }
}
