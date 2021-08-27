<?php

namespace App\Models;

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
        'table_name',
        'row_id',
        'row_data',
        'request_data',
        'created_pin',
        'created_at',
        'ip',
        'user_agent',
    ];

    /**
     * Метод создания строки лога
     * 
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Database\Eloquent\Model $data     Экземпляр затрагиваемой модели
     * @return \App\Models\Log
     */
    public static function log($request, $data) {

        return static::create([
            'table_name' => $data->getTable() ?? null,
            'row_id' => $data->id ?? null,
            'row_data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'request_data' => json_encode($request->all(), JSON_UNESCAPED_UNICODE),
            'created_pin' => "id={$request->__user->id},pin={$request->__user->pin}",
            'created_at' => date("Y-m-d H:i:s"),
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
        ]);

    }

}
