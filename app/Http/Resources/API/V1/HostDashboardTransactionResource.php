<?php

namespace App\Http\Resources\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HostDashboardTransactionResource extends JsonResource
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
            'transaction_number' => $this->payment->transaction_number,
            'total_price' => $this->total_price,
            'status' => $this->status,
            'user_name' => $this->user?->name,
            'user_avatar' => $this->user?->avatar,
            'parking_space_address' => $this->parkingSpace?->address,
            'start_time' => $this->start_time->format('M-d-Y h:i A'),
            'end_time' => $this->end_time->format('M-d-Y h:i A'),
            'estimated_hours' => $this->estimated_hours,
            'created_at' => $this->created_at?->format('M-d-Y h:i A'),
            'payment_status' => $this->payment?->status,
        ];
    }
}
