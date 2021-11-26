<?php

namespace App\Models\Incomings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SipInternalExtension extends Model
{
    use HasFactory;

    /**
     * Соединение с БД, которое должна использовать модель.
     *
     * @var string
     */
    protected $connection = "incomings";

    /**
     * Отношение внутренниз номеров к внешним
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function externals()
    {
        return $this->belongsToMany(
            SipExternalExtension::class,
            'sip_internal_to_external_extensions',
            'internal_id',
            'external_id'
        );
    }
}
