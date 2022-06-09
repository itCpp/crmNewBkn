<?php

namespace App\Http\Controllers\Requests;

use App\Models\RequestsSource;
use App\Models\RequestsSourcesResource;
use Exception;
use Illuminate\Support\Facades\Log;

trait AddRequestCounterTrait
{
    /**
     * Счетчик добавленных заявок
     * 
     * @param  int $source_id
     * @param  int $resource_id
     * @return null
     */
    public function countQuerySourceResource($source_id = null, $resource_id = null)
    {
        if (empty($this->count_query_source_resource_trait))
            $this->count_query_source_resource_trait = [];

        $counts = &$this->count_query_source_resource_trait;

        try {

            if (empty($counts['source'][$source_id]))
                $counts['source'][$source_id] = RequestsSource::find($source_id);

            if ($source = $counts['source'][$source_id]) {
                $source->count_requests++;
                $source->save();
            }

            if (empty($counts['resource'][$resource_id]))
                $counts['resource'][$resource_id] = RequestsSourcesResource::find($resource_id);

            if ($resource = $counts['resource'][$resource_id]) {
                $resource->count_requests++;
                $resource->save();
            }
        } finally {
            return null;
        }
    }
}
