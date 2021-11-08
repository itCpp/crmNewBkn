<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestsStorySector extends Model
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
        'old_sector',
        'new_sector',
        'story_id',
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
     * Метод создания строки лога
     * 
     * @param \App\Models\RequestsStory $story Экземпляр модели заявки
     * @param string|int $old Идентификатор предыдущего оператора
     * @return \App\Models\RequestsStorySector
     */
    public static function write($story, $old)
    {
        return static::create([
            'request_id' => $story->request_data['id'] ?? null,
            'old_sector' => (int) $old,
            'new_sector' => (int) $story->request_data['callcenter_sector'],
            'story_id' => $story->id,
            'created_at' => date("Y-m-d H:i:s"),
        ]);
    }
}
