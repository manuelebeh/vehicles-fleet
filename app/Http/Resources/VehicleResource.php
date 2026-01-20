<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
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
            'brand' => $this->brand,
            'model' => $this->model,
            'full_name' => $this->full_name,
            'license_plate' => $this->license_plate,
            'year' => $this->year,
            'color' => $this->color,
            'status' => $this->status,
            'is_available' => $this->isAvailable(),
            'reservations_count' => $this->whenCounted('reservations'),
            'reservations' => $this->when($this->relationLoaded('reservations'), fn() => ReservationResource::collection($this->reservations)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
