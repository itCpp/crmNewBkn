<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Отношения ролей
     * 
     * @var array
     */
    public $rolesTo = [
        'callCenterManager' => [
            'permissions' => [
                'rating_access',
                'requests_access',
                'requests_add',
                'requests_addr_change',
                'requests_all_sectors',
                'requests_comment_first',
                'requests_edit',
                'requests_flash_null_status',
                'requests_pin_change',
                'requests_pin_for_appointment',
                'requests_pin_set',
                'requests_pin_set_offline',
                'requests_sector_change',
                'requests_sector_set',
                'requests_send_sms',
                'requests_set_null_status',
                'user_auth_query',
                'user_auth_query_all_sectors'
            ],
            'statuses' => [1, 2, 3, 4, 5, 6, 7, 8],
            'tabs' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
        ],
        'admin' => [
            'permissions' => [
                'rating_access',
                'requests_access',
                'requests_add',
                'requests_addr_change',
                'requests_all_my_sector',
                'requests_comment_first',
                'requests_edit',
                'requests_flash_null_status',
                'requests_pin_change',
                'requests_pin_for_appointment',
                'requests_pin_set',
                'requests_send_sms',
                'requests_set_null_status',
                'user_auth_query'
            ],
            'statuses' => [1, 2, 3, 4, 5, 6, 8],
            'tabs' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
        ],
        'caller' => [
            'permissions' => [
                'rating_access',
                'requests_access',
                'requests_edit',
                'requests_send_sms',
                'requests_pin_for_appointment',
            ],
            'statuses' => [1, 2, 3, 4, 5],
            'tabs' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
        ],
        'administrative' => [
            'permissions' => [
                'rating_access',
                'rating_all_callcenters',
                'rating_show_admins',
                'sms_access',
                'sms_access_system',
                'clients_agreements_access',
                'clients_agreements_all',
                'admin_users',
            ],
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::factory()->defaultState()->create()->each(function ($role) {

            if (!isset($this->rolesTo[$role->role]))
                return;

            $to = $this->rolesTo[$role->role];

            // Связь роли с правами
            foreach ($to['permissions'] ?? [] as $permission) {
                $role->permissions()->attach($permission);
            }

            // Свзяь роли с вкладками
            foreach ($to['tabs'] ?? [] as $tab) {
                $role->tabs()->attach($tab);
            }

            // Свзяь роли со статусами заявок
            foreach ($to['statuses'] ?? [] as $status) {
                $role->statuses()->attach($status);
            }
        });
    }
}
