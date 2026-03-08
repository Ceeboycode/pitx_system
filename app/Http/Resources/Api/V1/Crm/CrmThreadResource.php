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
            'closed_at' => $this->closed_at?->toISOString(),
            'last_message_at' => $this->last_message_at?->toISOString(),
            'details' => $this->details,
            'messages_count' => $this->whenCounted('messages'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
