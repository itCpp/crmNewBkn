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
        'old_pin',
        'login',
        'callcenter_id',
        'callcenter_sector_id',
        'surname',
        'name',
        'patronymic',
        'password',
        'position_id',
        'telegram_id',
        'auth_type',
        'created_at',
        'deleted_at',
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
    public function roles()
    {
        return $this->belongsToMany("App\Models\Role", "users_roles", "user", "role");
    }

    /**
     * Личные права пользователя
     */
    public function permissions()
    {
        return $this->belongsToMany("App\Models\Permission", "users_permissions", "user_id", "permission_id");
    }

    /**
     * Доступные вкладки для пользователя
     */
    public function tabs()
    {
        return $this->belongsToMany(Tab::class, 'tab_user');
    }

    /**
     * Рабочее время сотрудника
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function worktime()
    {
        return $this->belongsTo(UserWorkTime::class, 'pin', 'user_pin');
    }

    /**
     * Получение информации о секторе сотрудника
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sector()
    {
        return $this->belongsTo(CallcenterSector::class, 'callcenter_sector_id', 'id');
    }
}
