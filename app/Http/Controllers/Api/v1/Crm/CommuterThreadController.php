<?php

namespace App\Http\Controllers\Api\V1\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Crm\IndexCommuterThreadRequest;
use App\Http\Requests\Api\V1\Crm\StoreCommuterThreadRequest;
use App\Http\Resources\Api\V1\Crm\CrmThreadResource;
use App\Models\CrmThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommuterThreadController extends Controller
{
    public function index(IndexCommuterThreadRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 20);

        $threads = CrmThread::query()
            ->where('created_by_user_id', $user->id)
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where('subject', 'like', '%' . $filters['search'] . '%')
            )
            ->when(
                filled($filters['category'] ?? null),
                fn ($query) => $query->where('category', $filters['category'])
            )
            ->when(
                filled($filters['status'] ?? null),
                // 'resolved' maps to is_closed=true; 'open' and 'ongoing' both map to is_closed=false
                fn ($query) => $query->where('is_closed', $filters['status'] === 'resolved')
            )
            ->withCount('messages')
            ->latest('last_message_at')
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return CrmThreadResource::collection($threads)->response();
    }

    public function store(StoreCommuterThreadRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $validated = $request->validated();

        $thread = DB::transaction(function () use ($user, $validated) {
            $thread = CrmThread::create([
                'company_id' => $user->company_id,
                'created_by_user_id' => $user->id,
                'category' => $validated['category'],
                'subject' => $validated['subject'],
                'details' => $validated['details'] ?? null,
                'last_message_at' => now(),
            ]);

            $thread->messages()->create([
                'sender_user_id' => $user->id,
                'body' => $validated['body'],
                'is_internal' => false,
            ]);

            return $thread;
        });

        return response()->json([
            'message' => 'Thread created successfully.',
            'data' => new CrmThreadResource($thread->load('messages')->loadCount('messages')),
        ], 201);
    }

    public function show(Request $request, CrmThread $thread): JsonResponse
    {
        $this->ensureThreadOwner($request, $thread);
        // Commuters cannot access resolved threads directly
        abort_if($thread->is_closed, 403, 'This report has been resolved.');

        return response()->json([
            'data' => new CrmThreadResource($thread->loadCount('messages')),
        ]);
    }

    private function ensureThreadOwner(Request $request, CrmThread $thread): void
    {
        abort_unless((int) $thread->created_by_user_id === (int) $request->user()->id, 403);
    }
}
