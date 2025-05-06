<?php

namespace App\Http\Resources\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowForUserHourlyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' => $this->id,
            'parking_space_id' => $this->parking_space_id,
            'available_slots' => $this->available_slots,
            'estimated_hours' => $this->estimated_hours,
            'estimated_price' => $this->estimated_price,
            'distance' => $this->distance,
            'parking_space' => $this->whenLoaded(
                'parkingSpace',
                function () {
                    return [
                        'id' => $this->parkingSpace->id,
                        'title' => $this->parkingSpace->title,
                        'address' => $this->parkingSpace->address,
                        'latitude' => $this->parkingSpace->latitude,
                        'longitude' => $this->parkingSpace->longitude,
                        'description' => $this->parkingSpace->description,
                        'gallery_images' => $this->parkingSpace->gallery_images,
                        'spot_details' => SpotDetailResource::collection($this->parkingSpace->spotDetails),
                        'driver_instructions' => DriverInstructionResource::collection($this->parkingSpace->DriverInstructions),
                        'reviews' => ReviewResource::collection($this->parkingSpace->reviews),
                        'review_count' => $this->review_count,
                        'average_rating' => $this->average_rating,
                        'platform_fee' => $this->platform_fee,
                    ];
                }
            ),

            'status' => ($this->available_slots > 0) ? 'available' : 'unavailable',
        ];
    }
}
