<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The primary key of the table.
     * 
     * @var string
     */
    protected $primaryKey = "role";

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'role',
        'name',
        'lvl',
        'comment',
    ];

    /**
     * Разрешения для роли
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, "roles_permissions", "role", "permission");
    }

    /**
     * Пользователи, относящиеся к роли
     */
    public function users()
    {
        return $this->belongsToMany("App\Models\User", "users_roles", "role", "user");
    }

    /**
     * Доступные вкладки для роли
     */
    public function tabs()
    {
        return $this->belongsToMany(Tab::class, 'tab_role', 'role_id');
    }

    /**
     * Доступные вкладки для роли
     */
    public function statuses()
    {
        return $this->belongsToMany(Status::class, 'status_role', 'role');
    }
}
