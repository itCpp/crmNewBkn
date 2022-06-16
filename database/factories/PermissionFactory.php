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
        ['admin_stats', 'Доступ к статистики'],
        ['admin_stats_expenses', 'Доступ к статистики по расходам'],
        ['calls_log_access', 'Доступ к разделу журнала вызовов'],
        ['clients_agreements_access', 'Доступ к договорным клиентам'],
        ['clients_agreements_all', 'Доступ ко всем договорам'],
        ['clients_consultation_access', 'Доступ к клиетам с бесплатной консультацией'],
        ['clients_show_phone', 'Может видеть номера телефонов клиента'],
        ['rating_access', 'Доступ к рейтингу'],
        ['rating_all_callcenters', 'Доступ к рейтингу всех колл-центров'],
        ['rating_show_admins', 'Может видеть рейтинг руководителей секторов'],
        ['rating_show_chiefs', 'Может видеть рейтинг руководителей коллл-центров'],
        ['requests_access', 'Доступ к заявкам'],
        ['requests_add', 'Может создавать новые заявки вручную'],
        ['requests_addr_change', 'Может менять адрес записи'],
        ['requests_all_callcenters', 'Видит заявки и операторов всех колл-центров'],
        ['requests_all_my_sector', 'Видит все заявки и операторов только своего сектора'],
        ['requests_all_sectors', 'Видит заявки и операторов всех секторов своего колл-центра'],
        ['requests_comment_first', 'Может оставлять первичный комментарий'],
        ['requests_edit', 'Может вносить изменения в заявку'],
        ['requests_flash_null_status', 'Выделять новые неназначенные операторам заявки'],
        ['requests_flash_records_status', 'Выделять заявки с неподтвержденными записями'],
        ['requests_hide_uplift_rows', 'Может скрывать поднятую заявку со статусом из необработанных'],
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
        ['user_create', 'Может создавать новго сотрудника'],
        ['user_data_any_show', 'Может открывать главную страницу любого сотрудника'],
        ['user_fines_access', 'Доступ к просмотру штрафов сотрудника'],
        ['user_fines_create', 'Может назначать штрафы сотруднику'],
        ['user_fines_delete', 'Может удалять назначенные штрафы сотруднику'],
        ['queues_access', 'Доступ к очередям текстовых заявок'],
        ['sms_access', 'Доступ к чтению смс сообщений'],
        ['sms_access_system', 'Доступ к сообщениям без заявки'],
        ['second_calls_access', 'Доступ ко вторичным звонкам'],
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
