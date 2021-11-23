<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Permission::class;

    /**
     * Начальные данные прав
     * 
     * @var array
     */
    public $permissions = [
        ['admin_access', 'Доступ к админ-панели'],
        ['admin_callsqueue', 'Может настраивать распределение звонков между секторами'],
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
        ['requests_send_sms', 'Может отправлять смс по заявке'],
        ['requests_send_sms_no_limit', 'Может отправлять смс сообщения без ограничений по времени и статуса заявки'],
        ['user_auth_query', 'Может обработать запрос авторизации пользователя'],
        ['user_auth_query_all', 'Может обработать запрос авторизации любого сотрудника'],
        ['user_auth_query_all_sectors', 'Может обработать запрос авторизации всех секторов своего коллцентра'],
        ['queues_access', 'Доступ к очередям текстовых заявок'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $rand = rand(0, count($this->permissions) - 1);

        return [
            'permission' => $this->permissions[$rand][0],
            'comment' => $this->permissions[$rand][1],
        ];
    }
}
