<?php

namespace App\Http\Controllers\Api\V1\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Crm\StoreCommuterMessageRequest;
use App\Http\Resources\Api\V1\Crm\CrmMessageResource;
use App\Models\CrmThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommuterMessageController extends Controller
{
    public function index(Request $request, CrmThread $thread): JsonResponse
    {
        $this->ensureThreadOwner($request, $thread);
        $perPage = max(1, min((int) $request->integer('per_page', 50), 100));

        $messages = $thread->messages()
            ->with(['sender:id,name', 'attachments'])
            ->orderBy('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return CrmMessageResource::collection($messages)->response();
    }

    public function store(StoreCommuterMessageRequest $request, CrmThread $thread): JsonResponse
    {
        $this->ensureThreadOwner($request, $thread);

        $message = $thread->messages()->create([
            'sender_user_id' => $request->user()->id,
            'body' => $request->validated()['body'],
            'is_internal' => false,
        ]);

        $thread->update([
            'last_message_at' => now(),
        ]);

        return response()->json([
            'message' => 'Message sent successfully.',
            'data' => new CrmMessageResource($message->load(['sender:id,name', 'attachments'])),
        ], 201);
    }

    private function ensureThreadOwner(Request $request, CrmThread $thread): void
    {
        abort_unless((int) $thread->created_by_user_id === (int) $request->user()->id, 403);
    }
}
