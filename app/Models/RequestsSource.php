<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestsSource extends Model
{

    use HasFactory;

    /**
     * Список ресурсов источника
     * 
     * @return \App\Models\RequestsSourcesResource
     */
    public function resources() {

        return $this->hasMany(RequestsSourcesResource::class, "source_id");

    }

}
