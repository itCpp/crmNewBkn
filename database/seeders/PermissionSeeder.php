<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{

    /**
     * Массив данных
     * 
     * @var array
     */
    public $rows = [
        [
            'permission' => "requests_set_null_status",
            'comment' => "Может устанавливать заявке статус \"Не обработано\"",
        ],
        [
            'permission' => "requests_sector_set",
            'comment' => "Может назначать заявку для сектора",
        ],
        [
            'permission' => "requests_sector_clear",
            'comment' => "Может обнулить сектор",
        ],
        [
            'permission' => "requests_sector_change",
            'comment' => "Может менять сектор в заявке",
        ],
        [
            'permission' => "requests_pin_set_offline",
            'comment' => "Может назначать оператора, находящегося в офлайне",
        ],
        [
            'permission' => "requests_pin_set",
            'comment' => "Может устанавливать оператора на заявку",
        ],
        [
            'permission' => "requests_pin_for_appointment",
            'comment' => "Может быть назначенным на заявку",
        ],
        [
            'permission' => "requests_pin_change",
            'comment' => "Может менять оператора на заявке",
        ],
        [
            'permission' => "requests_edit",
            'comment' => "Может вносить изменения в заявку",
        ],
        [
            'permission' => "requests_comment_first",
            'comment' => "Может оставлять первичный комментарий",
        ],
        [
            'permission' => "requests_all_sectors",
            'comment' => "Видит заявки и операторов всех секторов своего колл-центра",
        ],
        [
            'permission' => "requests_all_callcenters",
            'comment' => "Видит заявки и операторов всех колл-центров",
        ],
        [
            'permission' => "requests_add",
            'comment' => "Может создавать новые заявки вручную",
        ],
        [
            'permission' => "requests_access",
            'comment' => "Доступ к заявкам",
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->rows as $row) {
            Permission::create($row);
        }
    }
}
