<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmMessage;
use App\Models\CrmThread;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CrmMessageController extends Controller
{
    public function index(Request $request, CrmThread $thread): JsonResponse
    {
        $this->authorizeThreadAccess($request, $thread);

        $messages = CrmMessage::query()
            ->where('thread_id', $thread->id)
            ->with(['sender:id,name', 'attachments'])
            ->orderBy('created_at')
            ->paginate(50);

        return response()->json(['data' => $messages]);
    }

    public function store(Request $request, CrmThread $thread): JsonResponse
    {
        $user = $request->user();
        $this->authorizeThreadAccess($request, $thread);

        if ($thread->is_closed && ! $this->isInternalStaff($user)) {
            abort(422, 'Thread is closed.');
        }

        $validated = $request->validate([
            'body' => 'required|string',
            'is_internal' => 'sometimes|boolean',
        ]);

        $isInternal = (bool) ($validated['is_internal'] ?? false);

        if ($isInternal && ! $this->isInternalStaff($user)) {
            abort(403, 'Only internal staff can post internal notes.');
        }

        $message = $thread->messages()->create([
            'sender_user_id' => $user->id,
            'body' => $validated['body'],
            'is_internal' => $isInternal,
        ]);

        $thread->update(['last_message_at' => now()]);

        $message->load(['sender:id,name', 'attachments']);

        return response()->json([
            'data' => [
                'id' => $message->id,
                'body' => $message->body,
                'is_internal' => (bool) $message->is_internal,
                'created_at' => $message->created_at?->toISOString(),
                'created_at_human' => $message->created_at?->diffForHumans(),
                'sender' => $message->sender ? [
                    'id' => $message->sender->id,
                    'name' => $message->sender->name,
                ] : null,
                'attachments' => $message->attachments->map(fn ($attachment) => [
                    'id' => $attachment->id,
                    'original_name' => $attachment->original_name,
                    'mime_type' => $attachment->mime_type,
                    'size_bytes' => $attachment->size_bytes,
                    'preview_url' => Storage::disk($attachment->disk)->url($attachment->path),
                    'download_url' => route('crm.attachments.download', $attachment),
                ])->values(),
            ],
        ], 201);
    }

    private function authorizeThreadAccess(Request $request, CrmThread $thread): void
    {
        $user = $request->user();

        if ($this->canAssignThreads($user)) {
            return;
        }

        if ($this->isInternalStaff($user)) {
            abort_unless((int) $thread->assigned_to_user_id === (int) $user->id, 403);
            return;
        }

        abort_unless((int) $user->company_id === (int) $thread->company_id, 403);
    }

    private function canAssignThreads(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    private function isInternalStaff(User $user): bool
    {
        return $user->roles()->where('type', 'internal')->exists();
    }
}
