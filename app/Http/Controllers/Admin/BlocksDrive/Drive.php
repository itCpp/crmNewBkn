<?php

namespace App\Http\Controllers\Admin\BlocksDrive;

use App\Http\Controllers\Admin\Databases;
use App\Http\Controllers\Controller;
use App\Models\SettingsQueuesDatabase;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Drive extends Controller
{
    /**
     * Список сайтов с активной статистикой
     * 
     * @var array
     */
    protected $connections = [];

    /**
     * Список всех подключений
     * 
     * @var \Illuminate\Support\Collection
     */
    protected $databases;

    /**
     * Создание экземпляра объекта
     * 
     * @return void
     */
    public function __construct()
    {
        $this->connections = $this->findOwnStatistic();
    }

    /**
     * Проверка наличия статистики в базах
     * 
     * @return array
     */
    public function findOwnStatistic()
    {
        $this->databases = collect([]);

        foreach (SettingsQueuesDatabase::getAllDecrypt() as $config) {

            Databases::setConfig($config);
            $config['connection'] = Databases::getConnectionName($config['id']);

            try {
                if (Schema::connection($config['connection'])->hasTable('blocks')) {
                    $connected[] = $config['connection'];
                    $this->databases->push($config);
                }
            } catch (Exception) {
            }
        }

        return $connected ?? [];
    }
}
