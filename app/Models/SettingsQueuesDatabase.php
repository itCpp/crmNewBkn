<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SettingsQueuesDatabase extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'active',
        'host',
        'port',
        'user',
        'password',
        'database',
        'table_name',
    ];

    /**
     * Атрибуты, которые необходимо скрыть
     * 
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Преобразование данных
     * 
     * @var array
     */
    protected $casts = [
        'active' => "boolean",
    ];

    /**
     * Вывод всех подключений
     * 
     * @return array
     */
    public static function getAllDecrypt()
    {
        return static::where('active', 1)->get()->map(function ($row) {

            $row->host = $row->host ? Crypt::decryptString($row->host) : null;
            $row->port = $row->port ? Crypt::decryptString($row->port) : null;
            $row->user = $row->user ? Crypt::decryptString($row->user) : null;
            $row->database = $row->database ? Crypt::decryptString($row->database) : null;
            $row->table_name = $row->table_name ? Crypt::decryptString($row->table_name) : null;
            $password = $row->password ? Crypt::decryptString($row->password) : null;

            return array_merge($row->toArray(), [
                'password' => $password,
            ]);
        })->toArray();
    }
}
