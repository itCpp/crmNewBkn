<?php

namespace App\Http\Resources\Mailler;

use Illuminate\Http\Resources\Json\JsonResource;

class MaillerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $response = parent::toArray($request);

        $response['counter'] = $this->resource->counter;

        return $response;
    }
}
