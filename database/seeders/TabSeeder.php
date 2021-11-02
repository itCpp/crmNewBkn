<?php

namespace Database\Seeders;

use App\Models\Tab;
use Illuminate\Database\Seeder;

class TabSeeder extends Seeder
{
    /**
     * Данные
     * 
     * @var array
     */
    protected $tabs = [
        ['id' => 1, 'position' => 0, 'name' => 'Не обработана', 'name_title' => 'Новые или вновь поступившие заявки', 'where_settings' => '[{"attr": [{"column": "status_id"}], "where": "whereNull"}, {"attr": [{"value": "1", "column": "uplift", "operator": "="}], "where": "orWhere"}]', 'order_by_settings' => '[{"by": "DESC", "where": "orderBy", "column": "uplift_at"}, {"by": "DESC", "where": "orderBy", "column": "id"}]', 'request_all' => NULL, 'request_all_permit' => 1, 'date_view' => 1, 'date_types' => NULL, 'statuses' => NULL],
        ['id' => 2, 'position' => 1, 'name' => 'Все', 'name_title' => 'Все заявки за указанный период', 'where_settings' => NULL, 'order_by_settings' => '[{"by": "DESC", "where": "orderBy", "column": "uplift_at"}]', 'request_all' => NULL, 'request_all_permit' => 1, 'date_view' => 0, 'date_types' => '{"uplift_at": true, "created_at": true}', 'statuses' => NULL],
        ['id' => 3, 'position' => 2, 'name' => 'Москва', 'name_title' => 'Вывод московских заявок', 'where_settings' => '[{"attr": [{"value": "1", "column": "check_moscow", "operator": "="}], "where": "where"}]', 'order_by_settings' => '[{"by": "DESC", "where": "orderBy", "column": "uplift_at"}]', 'request_all' => NULL, 'request_all_permit' => 1, 'date_view' => 0, 'date_types' => NULL, 'statuses' => NULL],
        ['id' => 4, 'position' => 3, 'name' => 'Регионы', 'name_title' => 'Заявки за пределами Московской области', 'where_settings' => '[{"attr": [{"value": "0", "column": "check_moscow", "operator": "="}], "where": "where"}, {"attr": [{"column": "check_moscow"}], "where": "whereNull"}]', 'order_by_settings' => '[{"by": "DESC", "where": "orderBy", "column": "uplift_at"}]', 'request_all' => NULL, 'request_all_permit' => 1, 'date_view' => 0, 'date_types' => NULL, 'statuses' => NULL],
        ['id' => 5, 'position' => 4, 'name' => 'Записи', 'name_title' => NULL, 'where_settings' => NULL, 'order_by_settings' => '[{"by": "ASC", "where": "orderBy", "column": "event_at"}]', 'request_all' => NULL, 'request_all_permit' => 1, 'date_view' => 0, 'date_types' => '{"event_at": true}', 'statuses' => '[3, 4]'],
        ['id' => 6, 'position' => 6, 'name' => 'Слив', 'name_title' => NULL, 'where_settings' => NULL, 'order_by_settings' => '[{"by": "DESC", "where": "orderBy", "column": "event_at"}]', 'request_all' => 'sector', 'request_all_permit' => 1, 'date_view' => 0, 'date_types' => '{"event_at": true}', 'statuses' => '[8]'],
        ['id' => 7, 'position' => 5, 'name' => 'Приход', 'name_title' => NULL, 'where_settings' => NULL, 'order_by_settings' => '[{"by": "ASC", "where": "orderBy", "column": "event_at"}]', 'request_all' => NULL, 'request_all_permit' => 1, 'date_view' => 0, 'date_types' => '{"event_at": true}', 'statuses' => '[7]'],
        ['id' => 8, 'position' => 7, 'name' => 'Созвон', 'name_title' => NULL, 'where_settings' => NULL, 'order_by_settings' => '[{"by": "ASC", "where": "orderBy", "column": "event_at"}]', 'request_all' => NULL, 'request_all_permit' => 1, 'date_view' => 0, 'date_types' => '{"event_at": true}', 'statuses' => '[2]'],
        ['id' => 9, 'position' => 9, 'name' => 'Недозвон', 'name_title' => NULL, 'where_settings' => NULL, 'order_by_settings' => '[{"by": "DESC", "where": "orderBy", "column": "uplift_at"}]', 'request_all' => 'sector', 'request_all_permit' => 1, 'date_view' => 0, 'date_types' => '{"uplift_at": true, "created_at": true}', 'statuses' => '[1]'],
        ['id' => 10, 'position' => 8, 'name' => 'Брак', 'name_title' => NULL, 'where_settings' => NULL, 'order_by_settings' => '[{"by": "DESC", "where": "orderBy", "column": "updated_at"}]', 'request_all' => 'sector', 'request_all_permit' => 1, 'date_view' => 0, 'date_types' => '{"event_at": true, "uplift_at": true, "created_at": true, "updated_at": true}', 'statuses' => '[6]'],
        ['id' => 11, 'position' => 10, 'name' => 'БК', 'name_title' => 'Бесплатная консультация', 'where_settings' => NULL, 'order_by_settings' => '[{"by": "DESC", "where": "orderBy", "column": "updated_at"}]', 'request_all' => 'sector', 'request_all_permit' => 1, 'date_view' => 0, 'date_types' => NULL, 'statuses' => '[5]'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->tabs as $tab) {

            $row = new Tab;

            foreach ($row->getCasts() as $column => $type) {

                if ($type != "array" and $type != "object")
                    continue;

                $tab[$column] = json_decode($tab[$column], $type == "array");
            }

            foreach ($tab as $key => $value)
                $row->$key = $value;

            $row->save();
        }
    }
}
