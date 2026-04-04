<?php

namespace App\Http\Resources\Api\V1\Crm;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CrmThreadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'created_by_user_id' => $this->created_by_user_id,
            'assigned_to_user_id' => $this->assigned_to_user_id,
            'category' => $this->category,
            'subject' => $this->subject,
            'is_closed' => (bool) $this->is_closed,
            // Derived: resolved = closed, ongoing = has replies, open = new
            'status' => $this->is_closed
                ? 'resolved'
                : (($this->messages_count ?? 0) > 1 ? 'ongoing' : 'open'),
            'closed_at' => $this->closed_at?->toISOString(),
            'closed_at_human' => $this->closed_at?->diffForHumans(),
            'last_message_at' => $this->last_message_at?->toISOString(),
            'last_message_at_human' => $this->last_message_at?->diffForHumans(),
            'details' => $this->details,
            'messages_count' => $this->whenCounted('messages'),
            'first_message_id' => $this->whenLoaded('messages', fn () => $this->messages->first()?->id),
            'created_at' => $this->created_at?->toISOString(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
