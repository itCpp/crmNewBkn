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
    ];

    /**
     * Получить источник, относящийся к ресурсу
     * 
     * @return \App\Models\RequestsSource
     */
    public function source()
    {
        return RequestsSource::find($this->sourse_id);
    }
    
}
