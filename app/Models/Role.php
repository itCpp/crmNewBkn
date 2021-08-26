<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{

    use HasFactory;

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
     * Разрешения для роли
     */
    public function permissions() {

        return $this->hasMany("App\Models\RolesPermission", "role");

    }

}
