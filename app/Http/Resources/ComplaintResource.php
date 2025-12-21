<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'complaint_id'      => $this->complaint_id,
            'reference_number ' => $this->reference_number,
            'title'             => $this->title ?? null,
            'description'       => $this->description ?? null,
            'government_entity' => $this->governmentEntity->name ?? null,
            'status'            => $this->status,
            'created_at'        => $this->created_at,
        ];
    }
}
