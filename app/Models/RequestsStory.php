<?php

namespace App\Models;

use App\Http\Controllers\Controller;
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
        'row_data',
        'request_data',
        'created',
        'created_pin',
        'ip',
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
        'row_data' => 'array',
        'request_data' => 'array',
        'created' => 'boolean',
    ];

    /**
     * Метод создания строки лога
     * 
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Database\Eloquent\Model $data
     * @param boolean $created
     * @return \App\Models\RequestsStory
     */
    public static function write($request, $data, $created = false)
    {
        return static::create([
            'request_id' => $data->id ?? null,
            'row_data' => $data->toArray(),
            'request_data' => Controller::encrypt([
                'all' => $request->all(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'headers' => $request->header()
            ]),
            'created' => $created,
            'created_pin' => optional($request->user())->pin,
            'ip' => $request->ip(),
            'created_at' => date("Y-m-d H:i:s"),
        ]);
    }
}
