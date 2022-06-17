<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatRoom extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'user_to_user',
        'is_private',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Атрибуты, которые необходимо преобразовать
     * 
     * @var array
     */
    protected $casts = [
        'is_private' => "boolean",
    ];

    /**
     * Пользователи, относящиеся к чат-группе
     * 
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_rooms_user', 'chat_id', 'user_id');
    }
}
