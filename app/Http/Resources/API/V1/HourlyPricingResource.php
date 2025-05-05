<?php

namespace App\Http\Resources\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HourlyPricingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // 'parking_space_id' => $this->parking_space_id,
            'rate' => $this->rate,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'days' => HourlyPricingDayResource::collection($this->whenLoaded('days')),
        ];
    }
}
