<?php

namespace App\Models;

use App\Models\CrmMka\CrmUser as User;
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
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Пользователи, относящиеся к чат-группе
     * 
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_rooms_user', 'user_id', 'chat_id');
    }
}
