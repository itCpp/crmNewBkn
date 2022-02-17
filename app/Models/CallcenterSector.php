<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallcenterSector extends Model
{
    use HasFactory;

    /**
     * Данные колл-центра, которому принадлежит сектор
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function callcenter()
    {
        return $this->belongsTo(Callcenter::class);
    }
}
