<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestsClient extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые назначаются массово
     *
     * @var array
     */
    protected $fillable = [
        'phone',
        'hash',
    ];

    /**
     * Заявки, относящиеся к клиенту
     * 
     * @return \App\Models\RequestsRow
     */
    public function requests()
    {
        return $this->belongsToMany(RequestsRow::class, 'requests_rows_requests_clients', 'id_requests_clients', 'id_request');
    }

}
