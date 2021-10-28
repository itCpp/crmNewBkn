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
    protected $rows = [
        ['admin_access', 'Доступ к админ-панели'],
        ['admin_user_set_permission', 'Может устанавливать личные права сотрудникам'],
        ['admin_user_set_role', 'Может менять роли сотрудникам'],
        ['admin_users', 'Доступ администратора к созданию и редактированию данных сотрудников'],
        ['clients_show_phone', 'Может видеть номера телефонов клиента'],
        ['requests_access', 'Доступ к заявкам'],
        ['requests_add', 'Может создавать новые заявки вручную'],
        ['requests_addr_change', 'Может менять адрес записи'],
        ['requests_all_callcenters', 'Видит заявки и операторов всех колл-центров'],
        ['requests_all_my_sector', 'Видит все заявки и операторов только своего сектора'],
        ['requests_all_sectors', 'Видит заявки и операторов всех секторов своего колл-центра'],
        ['requests_comment_first', 'Может оставлять первичный комментарий'],
        ['requests_edit', 'Может вносить изменения в заявку'],
        ['requests_pin_change', 'Может менять оператора на заявке'],
        ['requests_pin_for_appointment', 'Может быть назначенным на заявку'],
        ['requests_pin_set', 'Может устанавливать оператора на заявку'],
        ['requests_pin_set_offline', 'Может назначать оператора, находящегося в офлайне'],
        ['requests_sector_change', 'Может менять сектор в заявке'],
        ['requests_sector_clear', 'Может обнулить сектор'],
        ['requests_sector_set', 'Может назначать заявку для сектора'],
        ['requests_set_null_status', 'Может устанавливать заявке статус "Не обработано"'],
        ['user_auth_query', 'Может обработать запрос авторизации пользователя'],
        ['user_auth_query_all', 'Может обработать запрос авторизации любого сотрудника'],
        ['user_auth_query_all_sectors', 'Может обработать запрос авторизации всех секторов своего коллцентра']
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->rows as $row) {
            Permission::create([
                'permission' => $row[0],
                'comment' => $row[1]
            ]);
        }
    }
}
