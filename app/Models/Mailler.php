<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mailler extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'type',
        'destination',
        'is_active',
        'config',
    ];

    /**
     * Атрибуты, которые будут преобразованы
     *
     * @var array
     */
    protected $casts = [
        'is_active' => "boolean",
        'config' => "array",
    ];

    /**
     * Отработанные действия
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function jobs()
    {
        return $this->hasMany(MaillerLog::class);
    }

    /**
     * Счетчик успешных отработок
     * 
     * @return int
     */
    public function getCounterAttribute()
    {
        return $this->jobs()->where('is_send', true)->count();
    }
}
