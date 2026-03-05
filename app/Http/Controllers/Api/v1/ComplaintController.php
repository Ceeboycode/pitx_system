<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Models\ComplaintMessageAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $complaints = Complaint::query()
            ->where('user_id', $user->id)
            ->with(['category:id,name'])
            ->withCount('messages')
            ->latest()
            ->paginate(20);

        return response()->json($complaints);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'complaint_category_id' => ['nullable','exists:complaint_categories,id'],
            'subject' => ['required','string','max:255'],

            // First message content + files
            'message' => ['required','string'],
            'attachments' => ['nullable','array'],
            'attachments.*' => ['file','max:20480'], // 20MB each
        ]);

        $result = DB::transaction(function () use ($user, $request, $data) {
            $complaint = Complaint::create([
                'user_id' => $user->id,
                'complaint_category_id' => $data['complaint_category_id'] ?? null,
                'subject' => $data['subject'],
                'status' => 'open',
                'priority' => 'normal',
                'source' => 'mobile',
            ]);

            $msg = ComplaintMessage::create([
                'complaint_id' => $complaint->id,
                'sender_user_id' => $user->id,
                'message' => $data['message'],
                'is_internal' => false,
            ]);

            foreach ($request->file('attachments', []) as $file) {
                $path = $file->store("complaints/{$complaint->id}/messages/{$msg->id}", 'public');

                ComplaintMessageAttachment::create([
                    'complaint_message_id' => $msg->id,
                    'disk' => 'public',
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }

            return $complaint->load(['category:id,name']);
        });

        return response()->json($result, 201);
    }

    public function show(Request $request, Complaint $complaint)
    {
        if ($complaint->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(
            $complaint->load(['category:id,name'])
        );
    }
}