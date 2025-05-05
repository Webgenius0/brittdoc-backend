<?php

namespace App\Http\Resources\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HostReservationIndexResource extends JsonResource
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
            'unique_id' => $this->unique_id,
            'user_name' => $this->user->name,
            'user_avatar' => $this->user->avatar,
            'parking_space_title' => $this->parkingSpace->title,
            'parking_space_address' => $this->parkingSpace->address,
            'reserve_time' => $this->start_time->format('M-d-Y h:i A') . ' - ' . $this->end_time->format('M-d-Y h:i A'),
            'total_price' => $this->total_price,
            'status' => $this->status,
            'duration' => $this->estimated_hours,
            'is_critical' => $this->is_critical,
            'is_expired' => $this->is_expired,
            'is_running' => $this->is_running,
            'parking_status' => $this->parking_status,
            // 'estimated_price' => $this->estimated_price,
        ];
        // return parent::toArray($request);
    }
}
