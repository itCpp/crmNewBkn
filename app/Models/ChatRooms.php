<?php

namespace App\Models;

use App\Models\CrmMka\CrmUser as User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatRooms extends Model
{
    use HasFactory;

    /**
     * Пользователи, относящиеся к чат-группе
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class, 'user_id', 'chat_id');
    }
}
