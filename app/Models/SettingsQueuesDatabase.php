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
        'host',
        'port',
        'user',
        'password',
        'database',
        'table_name',
    ];

    /**
     * Вывод всех подключений
     * 
     * @return array
     */
    public static function getAllDecrypt()
    {
        return static::all()->map(function ($row) {
            $row->host = $row->host ? Crypt::decryptString($row->host) : null;
            $row->port = $row->port ? Crypt::decryptString($row->port) : null;
            $row->user = $row->user ? Crypt::decryptString($row->user) : null;
            $row->password = $row->password ? Crypt::decryptString($row->password) : null;
            $row->database = $row->database ? Crypt::decryptString($row->database) : null;
            $row->table_name = $row->table_name ? Crypt::decryptString($row->table_name) : null;
            return $row;
        })->toArray();
    }
}
