<?php

namespace App\Models;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'connection_name',
        'database_name',
        'table_name',
        'row_id',
        'row_data',
        'request_data',
        'user_id',
        'created_at',
        'ip',
        'user_agent',
    ];

    /**
     * Метод создания строки лога
     * 
     * @param \Illuminate\Http\Request $request
     * @param mixed $data Экземпляр затрагиваемой модели
     * @param boolean $crypt Необходимо зашифровать данные
     * @return \App\Models\Log
     */
    public static function log($request, $data, $crypt = false)
    {
        $connection = $data?->getConnectionName() ?? null;
        $db = config("database.connections.{$connection}.database");
        $table = $data?->getTable() ?? null;

        $request_data = $request->all();

        if ($crypt)
            $request_data = Controller::encrypt($request_data);

        return static::create([
            'connection_name' => $connection,
            'database_name' => $db,
            'table_name' => $table,
            'row_id' => is_int($data->id ?? null) ? $data->id : null,
            'row_data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'request_data' => json_encode($request_data, JSON_UNESCAPED_UNICODE),
            'user_id' => $request->__user->id,
            'created_at' => date("Y-m-d H:i:s"),
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
        ]);
    }
}
