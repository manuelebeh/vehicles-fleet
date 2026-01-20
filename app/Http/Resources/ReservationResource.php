<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
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
            'user' => $this->when($this->relationLoaded('user'), fn() => new UserResource($this->user)),
            'vehicle' => $this->when($this->relationLoaded('vehicle'), fn() => new VehicleResource($this->vehicle)),
            'user_id' => $this->user_id,
            'vehicle_id' => $this->vehicle_id,
            'start_date' => $this->start_date?->toISOString(),
            'end_date' => $this->end_date?->toISOString(),
            'status' => $this->status,
            'is_pending' => $this->isPending(),
            'is_confirmed' => $this->isConfirmed(),
            'is_cancelled' => $this->isCancelled(),
            'is_completed' => $this->isCompleted(),
            'purpose' => $this->purpose,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
