<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Paginated list of jobs for the API.
 * Keeps links (first/last/prev/next) and meta (page, per_page, total...), but drops
 * Laravel's `meta.links` array: it is meant for HTML pagers and contains HTML entities
 * like "&laquo; Previous", which API clients don't need.
 */
class ServiceJobCollection extends ResourceCollection
{
    public $collects = ServiceJobResource::class;

    /**
     * @param  array<string, mixed>  $paginated
     * @param  array<string, mixed>  $default
     * @return array<string, mixed>
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        unset($default['meta']['links']);

        return $default;
    }
}
