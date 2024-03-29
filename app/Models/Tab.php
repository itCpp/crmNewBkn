<?php

namespace App\Models;

use App\Http\Controllers\Settings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tab extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'position',
        'name',
        'name_title',
        'where_settings',
        'order_by_settings',
        'request_all_permit',
        'date_view',
        'date_types',
        'statuses',
        'statuses_not',
        'counter_source',
        'counter_offices',
        'counter_next_day',
        'counter_hide_page',
        'flash_null',
        'flash_records_confirm',
        'check_for_show',
        'label_counter',
        'check_lost_requests',
    ];

    /**
     * Атрибуты, которые преобразовываются в json
     *
     * @var array
     */
    protected $casts = [
        'where_settings' => 'array',
        'order_by_settings' => 'array',
        'date_types' => 'array',
        'statuses' => 'array',
        'statuses_not' => 'array',
        'counter_source' => 'boolean',
        'counter_offices' => 'boolean',
        'counter_next_day' => 'boolean',
        'counter_hide_page' => 'boolean',
        'flash_null' => 'boolean',
        'flash_records_confirm' => 'boolean',
        'label_counter' => 'boolean',
        'check_lost_requests' => 'boolean',
    ];

    /**
     * Атрибуты, которые будут переданы в массив конфига
     * 
     * @var array
     */
    protected $to_settings = [
        'id',
        'counter_source',
        'counter_offices',
        'counter_next_day',
        'counter_hide_page',
        'flash_null',
        'flash_records_confirm',
        'label_counter',
        'check_lost_requests',
    ];

    /**
     * Выводит настройки вкладки
     * 
     * @return array
     */
    public function getSettings()
    {
        foreach ($this->to_settings as $row)
            $settings[$row] = $this->$row;

        $settings['intervalTimer'] = (new Settings)->CHECK_LOST_REQUESTS_TAB_PERIOD_TIME;

        return $settings ?? [];
    }
}
