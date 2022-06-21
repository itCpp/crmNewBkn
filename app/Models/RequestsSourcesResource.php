<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestsSourcesResource extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'sourse_id',
        'type',
        'val',
        'check_site',
    ];

    /**
     * Атрибуты, которые преобразовываются в json
     *
     * @var array
     */
    protected $casts = [
        'check_site' => 'boolean',
    ];

    /**
     * Получить источник, относящийся к ресурсу
     * 
     * @return \App\Models\RequestsSource
     */
    public function source()
    {
        return $this->belongsTo(RequestsSource::class, 'source_id');
    }
    
}
