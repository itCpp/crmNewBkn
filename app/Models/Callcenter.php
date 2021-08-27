<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Callcenter extends Model
{

    use HasFactory;

    /**
     * Сектора колл-центра
     */
    public function sectors() {

        return $this->hasMany("App\Models\CallcenterSector");

    }

}
