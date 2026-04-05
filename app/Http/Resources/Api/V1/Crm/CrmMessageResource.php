<?php

namespace App\Http\Resources\Api\V1\Crm;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CrmMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'thread_id' => $this->thread_id,
            'sender' => $this->whenLoaded('sender', fn () => $this->sender ? [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
            ] : null),
            'body' => $this->body,
            'is_internal' => (bool) $this->is_internal,
            'attachments' => $this->whenLoaded('attachments', fn () => $this->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'original_name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
                'size_bytes' => $attachment->size_bytes,
                'url' => Storage::disk($attachment->disk)->url($attachment->path),
                'preview_url' => Storage::disk($attachment->disk)->url($attachment->path),
                'download_url' => route('crm.attachments.download', $attachment),
            ])->values()),
            'created_at' => $this->created_at?->toISOString(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
