<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Company\BlockHost::where('is_hostname', 0)->lazy()->each(function ($row) { App\Models\BlockIp::create(['ip' => $row->host, 'is_block' => $row->block]); });
 */
class BlockIp extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ip',
        'hostname',
        'is_period',
        'period_data',
        'sites',
        'created_at',
        'updated_at',
    ];

    /**
     * Атрибуты, которые будут преобразованы
     *
     * @var array
     */
    protected $casts = [
        'is_period' => 'boolean',
        'sites' => 'array',
        'period_data' => 'array',
    ];
}
