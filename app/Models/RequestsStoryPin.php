<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestsStoryPin extends Model
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
        'old_pin',
        'new_pin',
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
     * @return \App\Models\RequestsStoryPin
     */
    public static function write($story, $old)
    {
        return static::create([
            'request_id' => $story->row_data['id'] ?? null,
            'old_pin' => $old,
            'new_pin' => $story->row_data['pin'],
            'story_id' => $story->id,
            'created_at' => date("Y-m-d H:i:s"),
        ]);
    }
}
