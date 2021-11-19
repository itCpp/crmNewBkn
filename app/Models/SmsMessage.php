<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsMessage extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'message_id',
        'gate',
        'channel',
        'created_pin',
        'phone',
        'message',
        'direction',
        'sent_at',
    ];

    /**
     * Привязанные заявки к сообщению
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function requests()
    {
        return $this->belongsToMany(RequestsRow::class, 'sms_request', 'sms_id', 'request_id');
    }
}
