<?php

namespace App\Http\Controllers\Api\V1\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmMessage;
use App\Models\CrmMessageAttachment;
use App\Models\CrmThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommuterAttachmentController extends Controller
{
    public function store(Request $request, CrmThread $thread, CrmMessage $message): JsonResponse
    {
        $this->ensureThreadOwner($request, $thread);

        abort_unless((int) $message->thread_id === (int) $thread->id, 404);

        $validated = $request->validate([
            'file' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $file = $validated['file'];
        $disk = 'public';
        $folder = "crm/commuter-{$request->user()->id}/thread-{$thread->id}/message-{$message->id}";
        $name = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs($folder, $name, $disk);

        $attachment = CrmMessageAttachment::create([
            'thread_id' => $thread->id,
            'message_id' => $message->id,
            'uploaded_by_user_id' => $request->user()->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        return response()->json([
            'message' => 'Attachment uploaded successfully.',
            'data' => [
                'id' => $attachment->id,
                'original_name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
                'size_bytes' => $attachment->size_bytes,
                'url' => Storage::disk($disk)->url($path),
            ],
        ], 201);
    }

    private function ensureThreadOwner(Request $request, CrmThread $thread): void
    {
        abort_unless((int) $thread->created_by_user_id === (int) $request->user()->id, 403);
    }
}
