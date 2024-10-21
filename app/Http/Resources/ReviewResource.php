<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
class ReviewResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer?->first_name . ' ' . $this->customer?->last_name,
            'customer_image' => getImage($this->customer, 'profile'),
            'rating' => $this->rating,
            'review_text' => $this->review_text,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'ago' => $this->created_at?->diffForHumans(),
        ];
    }
}
