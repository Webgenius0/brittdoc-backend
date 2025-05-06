<?php

namespace App\Http\Resources\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParkingSpaceResource extends JsonResource
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
            'slug' => $this->slug,
            'user_id' => $this->user_id,
            'unique_id' => $this->unique_id,
            'title' => $this->title,
            'type_of_spot' => $this->type_of_spot,
            'max_vehicle_size' => $this->max_vehicle_size,
            'total_slots' => $this->total_slots,
            'description' => $this->description,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address' => $this->address,
            'gallery_images' => $this->gallery_images,
            'total_reviews' => $this->total_reviews??null,
            'average_rating' => $this->average_rating ? number_format($this->average_rating, 1) : null,
            'status' => $this->status,
            'driver_instructions' => DriverInstructionResource::collection($this->whenLoaded('driverInstructions')),
            'hourly_pricing' => HourlyPricingResource::collection($this->whenLoaded('hourlyPricing')),
            'daily_pricing' => DailyPricingResource::collection($this->whenLoaded('dailyPricing')),
            'monthly_pricing' => MonthlyPricingResource::collection($this->whenLoaded('monthlyPricing')),
            'spot_details' => SpotDetailResource::collection($this->whenLoaded('spotDetails')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
