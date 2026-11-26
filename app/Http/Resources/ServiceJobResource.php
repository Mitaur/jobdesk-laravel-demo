<?php

namespace App\Http\Resources;

use App\Models\ServiceJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServiceJob */
class ServiceJobResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'price' => $this->price !== null ? (float) $this->price : null,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'notes' => $this->notes,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => ['self' => route('api.jobs.show', $this->id)],
        ];
    }
}
