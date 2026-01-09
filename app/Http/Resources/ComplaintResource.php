<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ComplaintResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'complaint_id'       => $this->complaint_id,
            'reference_number'   => $this->reference_number,
            'type'               => $this->type ?? null,
            'description'        => $this->description ?? null,
            'location'           => $this->location ?? null,
            'government_entity'  => $this->whenLoaded('governmentEntity', function() {
                return [
                    'id'   => $this->government_entity_id,
                    'name' => $this->governmentEntity->name ?? null,
                ];
            }, $this->government_entity_id), // Fallback to just ID if not loaded
            'status'             => $this->status,
            'created_at'         => $this->created_at,

            // Only include attachments when explicitly requested or loaded
            'attachments' => $this->when($request->get('include_attachments') ||
                                         $this->relationLoaded('attachments'), function() {
                return $this->attachments->map(function ($attachment) {
                    return [
                        'id'        => $attachment->id,
                        'file_path' => Storage::url($attachment->file_path),
                        'type'      => $attachment->type ?? null,
                    ];
                });
            }),
        ];
    }
}
