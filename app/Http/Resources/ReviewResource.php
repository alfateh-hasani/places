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
            'rating' => $this->rating,
            'review_text' => $this->review_text,
        ];
    }
}
