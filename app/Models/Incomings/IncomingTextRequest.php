<?php

namespace App\Models\Incomings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomingTextRequest extends Model
{
    use HasFactory;

    /**
     * Соединение с БД, которое должна использовать модель.
     *
     * @var string
     */
    protected $connection = "incomings";

    /**
     * The attributes that should be cast
     *
     * @var array
     */
    protected $casts = [
        'response_data' => 'object',
    ];

    /**
     * Отношения к событию
     * 
     * @return 
     */
    public function event()
    {
        return $this->belongsTo(IncomingEvent::class, 'incoming_event_id');
    }

}
