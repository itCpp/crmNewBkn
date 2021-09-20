<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestsStory extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'request_id',
        'request_data',
        'created_pin',
        'created_at',
    ];

    /**
     * Поля типа Carbon
     * 
     * @var array
     */
    protected $dates = [
        'created_at',
    ];

    /**
     * Атрибуты, которые преобразовываются в json
     *
     * @var array
     */
    protected $casts = [
        'request_data' => 'array',
    ];

    /**
     * Метод создания строки лога
     * 
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Database\Eloquent\Model $data
     * @return \App\Models\RequestsStory
     */
    public static function write($request, $data)
    {

        return static::create([
            'request_id' => $data->id ?? null,
            'request_data' => $data,
            'created_pin' => $request->__user->pin ?? null,
            'created_at' => date("Y-m-d H:i:s"),
        ]);

    }

}
