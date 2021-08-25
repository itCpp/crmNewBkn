<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'pin',
        'login',
        'callcenter_id',
        'callcenter_sector_id',
        'surname',
        'name',
        'patronymic',
        'password',
        'telegram_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Роли пользователя
     */
    public function roles() {

        return $this->hasMany("App\Models\UsersRole", "user");

    }

    /**
     * Личные права пользователя
     */
    public function permissions() {

        return $this->hasMany("App\Models\UsersPermission", "user");

    }

}
