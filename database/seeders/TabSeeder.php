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
        [
            'id' => 1,
            'position' => 0,
            'name' => 'Не обработана',
            'name_title' => 'Новые или вновь поступившие заявки',
            'where_settings' => [
                [
                    "attr" => [
                        [
                            "column" => "status_id"
                        ]
                    ],
                    "where" => "whereNull"
                ],
                [
                    "attr" => [
                        [
                            "value" => "1",
                            "column" => "uplift",
                            "operator" => "="
                        ]
                    ],
                    "where" => "orWhere"
                ]
            ],
            'order_by_settings' => [
                [
                    "by" => "DESC",
                    "where" => "orderBy",
                    "column" => "uplift_at",
                ],
                [
                    "by" => "DESC",
                    "where" => "orderBy",
                    "column" => "id",
                ],
            ],
            'request_all' => NULL,
            'request_all_permit' => 1,
            'date_view' => 1,
            'date_types' => NULL,
            'statuses' => [],
            'statuses_not' => [],
        ],
        [
            'id' => 2,
            'position' => 1,
            'name' => 'Все',
            'name_title' => 'Все заявки за указанный период',
            'where_settings' => NULL,
            'order_by_settings' => [
                [
                    "by" => "DESC",
                    "where" => "orderBy",
                    "column" => "uplift_at",
                ],
            ],
            'request_all' => NULL,
            'request_all_permit' => 1,
            'date_view' => 0,
            'date_types' => [
                "uplift_at" => true,
                "created_at" => true,
            ],
            'statuses' => [],
            'statuses_not' => [],
        ],
        [
            'id' => 3,
            'position' => 2,
            'name' => 'Москва',
            'name_title' => 'Вывод московских заявок',
            'where_settings' => [
                [
                    "attr" => [
                        [
                            "value" => "1",
                            "column" => "check_moscow",
                            "operator" => "=",
                        ],
                    ],
                    "where" => "where",
                ],
                [
                    "attr" => [
                        [
                            "column" => "check_moscow",
                        ],
                    ],
                    "where" => "orWhereNull",
                ],
            ],
            'order_by_settings' => [
                [
                    "by" => "DESC",
                    "where" => "orderBy",
                    "column" => "uplift_at",
                ],
            ],
            'request_all' => 'my',
            'request_all_permit' => 1,
            'date_view' => 0,
            'date_types' => [
                "uplift_at" => true,
                "created_at" => true,
            ],
            'statuses' => [],
            'statuses_not' => [6],
        ],
        [
            'id' => 4,
            'position' => 3,
            'name' => 'Регионы',
            'name_title' => 'Заявки за пределами Московской области',
            'where_settings' => [
                [
                    "attr" => [
                        [
                            "value" => "0",
                            "column" => "check_moscow",
                            "operator" => "=",
                        ],
                    ],
                    "where" => "where",
                ],
                [
                    "attr" => [
                        [
                            "column" => "status_id",
                        ],
                    ],
                    "where" => "whereNotNull",
                ],
            ],
            'order_by_settings' => [
                [
                    "by" => "DESC",
                    "where" => "orderBy",
                    "column" => "uplift_at",
                ],
            ],
            'request_all' => 'my',
            'request_all_permit' => 1,
            'date_view' => 0,
            'date_types' => [
                "uplift_at" => true,
                "created_at" => true
            ],
            'statuses' => [],
            'statuses_not' => [],
        ],
        [
            'id' => 5,
            'position' => 4,
            'name' => 'Записи',
            'name_title' => 'Клиенты, записанные на приход',
            'where_settings' => [],
            'order_by_settings' => [
                [
                    "by" => "ASC",
                    "where" => "orderBy",
                    "column" => "event_at",
                ],
            ],
            'request_all' => 'my',
            'request_all_permit' => 1,
            'date_view' => 0,
            'date_types' => [
                "event_at" => true
            ],
            'statuses' => [3, 4],
            'statuses_not' => [],
        ],
        [
            'id' => 6,
            'position' => 6,
            'name' => 'Слив',
            'name_title' => 'Клиенты, отказавшиеся от прихода по записи',
            'where_settings' => [],
            'order_by_settings' => [
                [
                    "by" => "DESC",
                    "where" => "orderBy",
                    "column" => "event_at",
                ],
            ],
            'request_all' => 'sector',
            'request_all_permit' => 1,
            'date_view' => 0,
            'date_types' => [
                "event_at" => true,
            ],
            'statuses' => [8],
            'statuses_not' => [],
        ],
        [
            'id' => 7,
            'position' => 5,
            'name' => 'Приход',
            'name_title' => 'Клиенты, которые дошли до офиса по записи',
            'where_settings' => [],
            'order_by_settings' => [
                [
                    "by" => "ASC",
                    "where" => "orderBy",
                    "column" => "event_at",
                ],
            ],
            'request_all' => 'my',
            'request_all_permit' => 1,
            'date_view' => 0,
            'date_types' => [
                "event_at" => true,
            ],
            'statuses' => [7],
            'statuses_not' => [],
        ],
        [
            'id' => 8,
            'position' => 7,
            'name' => 'Созвон',
            'name_title' => 'Ожидается созвон с клиентом',
            'where_settings' => [],
            'order_by_settings' => [
                [
                    "by" => "ASC",
                    "where" => "orderBy",
                    "column" => "event_at",
                ],
            ],
            'request_all' => 'my',
            'request_all_permit' => 1,
            'date_view' => 0,
            'date_types' => [
                "event_at" => true,
            ],
            'statuses' => [2],
            'statuses_not' => [],
        ],
        [
            'id' => 9,
            'position' => 9,
            'name' => 'Недозвон',
            'name_title' => NULL,
            'where_settings' => NULL,
            'order_by_settings' => [
                [
                    "by" => "DESC",
                    "where" => "orderBy",
                    "column" => "uplift_at",
                ],
            ],
            'request_all' => 'sector',
            'request_all_permit' => 1,
            'date_view' => 0,
            'date_types' => [
                "uplift_at" => true,
                "created_at" => true,
            ],
            'statuses' => [1],
            'statuses_not' => [],
        ],
        [
            'id' => 10,
            'position' => 8,
            'name' => 'Брак',
            'name_title' => NULL,
            'where_settings' => NULL,
            'order_by_settings' => [
                [
                    "by" => "DESC",
                    "where" => "orderBy",
                    "column" => "updated_at",
                ],
            ],
            'request_all' => 'sector',
            'request_all_permit' => 1,
            'date_view' => 0,
            'date_types' =>  [
                "event_at" => true,
                "uplift_at" => true,
                "created_at" => true,
                "updated_at" => true,
            ],
            'statuses' => [6],
            'statuses_not' => [],
        ],
        [
            'id' => 11,
            'position' => 10,
            'name' => 'Все БК',
            'name_title' => 'Бесплатная консультация',
            'where_settings' => [],
            'order_by_settings' => [
                [
                    "by" => "DESC",
                    "where" => "orderBy",
                    "column" => "updated_at",
                ],
            ],
            'request_all' => 'sector',
            'request_all_permit' => 1,
            'date_view' => 0,
            'date_types' => [
                "uplift_at" => true,
                "created_at" => true,
            ],
            'statuses' => [5],
            'statuses_not' => [],
        ],
        [
            'id' => 12,
            'position' => 11,
            'name' => 'Мои БК',
            'name_title' => 'Только заявки оператора с бесплатной консультацией',
            'where_settings' => [],
            'order_by_settings' => [
                [
                    "by" => "DESC",
                    "where" => "orderBy",
                    "column" => "updated_at",
                ],
            ],
            'request_all' => 'my',
            'request_all_permit' => 0,
            'date_view' => 0,
            'date_types' => [
                "uplift_at" => true,
                "created_at" => true,
            ],
            'statuses' => [5],
            'statuses_not' => [],
        ],
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

                // if ($type == "array" or $type == "object")
                //     $tab[$column] = json_decode($tab[$column], $type == "array");
            }

            foreach ($tab as $key => $value)
                $row->$key = $value;

            $row->save();
        }
    }
}
