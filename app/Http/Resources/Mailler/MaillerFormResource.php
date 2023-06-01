<?php

namespace App\Http\Resources\Mailler;

use App\Models\RequestsRow;
use App\Models\Status;
use Illuminate\Http\Resources\Json\JsonResource;

class MaillerFormResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => $this->resource ? new MaillerResource($this->resource) : [],
            'statuses' => Status::whereNull('deleted_at')
                ->orderBy('name')
                ->get()
                ->map(fn ($status) => [
                    'key' => $status->id,
                    'value' => $status->id,
                    'text' => $status->name,
                ])
                ->toArray(),
            'variables' => collect((new RequestsRow)->variable_list)
                ->keys()
                ->merge(['phone', 'phone7x'])
                ->unique()
                ->sort()
                ->values()
                ->toArray(),
        ];
    }
}
