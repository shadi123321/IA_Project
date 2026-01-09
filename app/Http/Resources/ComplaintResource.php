<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'type'              => $this->type ?? null,
            'description'       => $this->description ?? null,
            'location'          => $this->location ?? null,
            'government_entity' => $this->governmentEntity->name ?? null,
            'status'            => $this->status,
            'created_at'        => $this->created_at,

            'attachments'       => $this->attachments->map(function ($attachment) {
                return [
                    'id'        => $attachment->id,
                    'file_path' => Storage::url($attachment->file_path),
                    'type'      => $attachment->type ?? null,
                ];
            }),
        ];
    }
}
