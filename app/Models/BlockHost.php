<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Company\BlockHost::where('is_hostname', 1)->lazy()->each(function ($row) { App\Models\BlockHost::create(['host' => $row->host, 'is_block' => $row->block]); });
 */
class BlockHost extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'host',
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
        'sites' => 'array'
    ];
}
