<?php

use App\Models\Company;
use App\Models\CrmMessage;
use App\Models\CrmMessageAttachment;
use App\Models\CrmThread;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

function internalRole(): Role
{
    return Role::firstOrCreate([
        'name' => 'internal-crm',
        'guard_name' => 'web',
    ], [
        'type' => 'internal',
    ]);
}

function externalRole(): Role
{
    return Role::firstOrCreate([
        'name' => 'external-crm',
        'guard_name' => 'web',
    ], [
        'type' => 'external',
    ]);
}

function superAdminRole(): Role
{
    return Role::firstOrCreate([
        'name' => 'super-admin',
        'guard_name' => 'web',
    ], [
        'type' => 'internal',
    ]);
}

function makeInternalUser(array $attributes = []): User
{
    $user = User::factory()->internal()->create($attributes);
    $user->assignRole(internalRole());

    return $user;
}

function makeSuperAdminUser(array $attributes = []): User
{
    $user = User::factory()->internal()->create($attributes);
    $user->assignRole(superAdminRole());

    return $user;
}

function makeCompany(User $creator): Company
{
    return Company::factory()->verified()->create([
        'created_by' => $creator->id,
        'updated_by' => $creator->id,
    ]);
}

function makeExternalUser(Company $company, array $attributes = []): User
{
    $user = User::factory()->external($company->id)->create($attributes);
    $user->assignRole(externalRole());

    return $user;
}

function makeThread(User $creator, ?User $assignee = null): CrmThread
{
    return CrmThread::query()->create([
        'company_id' => $creator->company_id,
        'created_by_user_id' => $creator->id,
        'assigned_to_user_id' => $assignee?->id,
        'category' => 'system',
        'subject' => 'Hosted CRM readiness',
        'details' => ['source' => 'mobile'],
        'last_message_at' => now(),
    ]);
}

test('super admins can view assign close and reopen crm threads', function () {
    $dispatcher = makeSuperAdminUser();
    $assignee = makeInternalUser();
    $company = makeCompany($dispatcher);
    $commuterSupportUser = makeExternalUser($company);
    $thread = makeThread($commuterSupportUser);

    $this->actingAs($dispatcher)
        ->getJson("/crm/threads/{$thread->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $thread->id)
        ->assertJsonPath('data.company.company_name', $company->company_name)
        ->assertJsonPath('data.category', 'system');

    $this->actingAs($dispatcher)
        ->patchJson("/crm/threads/{$thread->id}", [
            'assigned_to_user_id' => $assignee->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.assigned_to.id', $assignee->id);

    expect($thread->fresh()->assigned_to_user_id)->toBe($assignee->id);

    $this->actingAs($dispatcher)
        ->patchJson("/crm/threads/{$thread->id}/close")
        ->assertOk()
        ->assertJsonPath('data.is_closed', true);

    $thread->refresh();

    expect($thread->is_closed)->toBeTrue();
    expect($thread->closed_at)->not->toBeNull();

    $this->actingAs($dispatcher)
        ->patchJson("/crm/threads/{$thread->id}/reopen")
        ->assertOk()
        ->assertJsonPath('data.is_closed', false);

    $thread->refresh();

    expect($thread->is_closed)->toBeFalse();
    expect($thread->closed_at)->toBeNull();
});

test('regular internal staff cannot assign crm threads', function () {
    $dispatcher = makeInternalUser();
    $assignee = makeInternalUser();
    $company = makeCompany($dispatcher);
    $commuterSupportUser = makeExternalUser($company);
    $thread = makeThread($commuterSupportUser, $dispatcher);

    $this->actingAs($dispatcher)
        ->getJson("/crm/threads/{$thread->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $thread->id)
        ->assertJsonPath('data.company.company_name', $company->company_name)
        ->assertJsonPath('data.category', 'system');

    $this->actingAs($dispatcher)
        ->patchJson("/crm/threads/{$thread->id}", [
            'assigned_to_user_id' => $assignee->id,
        ])
        ->assertForbidden();

    expect($thread->fresh()->assigned_to_user_id)->toBe($dispatcher->id);

    $this->actingAs($dispatcher)
        ->patchJson("/crm/threads/{$thread->id}/close")
        ->assertOk()
        ->assertJsonPath('data.is_closed', true);

    $thread->refresh();

    expect($thread->is_closed)->toBeTrue();
    expect($thread->closed_at)->not->toBeNull();

    $this->actingAs($dispatcher)
        ->patchJson("/crm/threads/{$thread->id}/reopen")
        ->assertOk()
        ->assertJsonPath('data.is_closed', false);

    $thread->refresh();

    expect($thread->is_closed)->toBeFalse();
    expect($thread->closed_at)->toBeNull();
});

test('regular internal staff only see threads assigned to them', function () {
    $dispatcher = makeInternalUser();
    $otherInternalUser = makeInternalUser();
    $superAdmin = makeSuperAdminUser();
    $company = makeCompany($superAdmin);
    $commuterSupportUser = makeExternalUser($company);
    $assignedThread = makeThread($commuterSupportUser, $dispatcher);
    $otherThread = makeThread($commuterSupportUser, $otherInternalUser);
    $unassignedThread = makeThread($commuterSupportUser);

    $this->actingAs($dispatcher)
        ->get('/crm/threads')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Crm/Threads/Index')
            ->where('threads.data.0.id', $assignedThread->id)
            ->where('threads.total', 1)
            ->where('currentUser.can_assign_threads', false)
            ->where('staffUsers', [])
        );

    $this->actingAs($dispatcher)
        ->getJson("/crm/threads/{$otherThread->id}")
        ->assertForbidden();

    $this->actingAs($dispatcher)
        ->getJson("/crm/threads/{$unassignedThread->id}")
        ->assertForbidden();
});

test('internal staff cannot assign crm threads to external users', function () {
    $dispatcher = makeSuperAdminUser();
    $company = makeCompany($dispatcher);
    $externalUser = makeExternalUser($company);
    $thread = makeThread($externalUser);

    $this->actingAs($dispatcher)
        ->patchJson("/crm/threads/{$thread->id}", [
            'assigned_to_user_id' => $externalUser->id,
        ])
        ->assertStatus(422);

    expect($thread->fresh()->assigned_to_user_id)->toBeNull();
});

test('internal staff can post internal notes and attachments to crm threads', function () {
    Storage::fake('public');

    $dispatcher = makeInternalUser();
    $superAdmin = makeSuperAdminUser();
    $company = makeCompany($superAdmin);
    $externalUser = makeExternalUser($company);
    $thread = makeThread($externalUser, $dispatcher);

    $messageResponse = $this->actingAs($dispatcher)
        ->postJson("/crm/threads/{$thread->id}/messages", [
            'body' => 'Investigating the reported issue now.',
            'is_internal' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.is_internal', true)
        ->assertJsonPath('data.sender.id', $dispatcher->id);

    $messageId = $messageResponse->json('data.id');

    expect($messageId)->not->toBeNull();
    expect(CrmMessage::query()->find($messageId)?->body)->toBe('Investigating the reported issue now.');
    expect($thread->fresh()->last_message_at)->not->toBeNull();

    $uploadResponse = $this->actingAs($dispatcher)
        ->post("/crm/threads/{$thread->id}/messages/{$messageId}/attachments", [
            'file' => UploadedFile::fake()->create('investigation-note.txt', 8, 'text/plain'),
        ]);

    $uploadResponse->assertCreated()
        ->assertJsonPath('data.original_name', 'investigation-note.txt');

    $attachment = CrmMessageAttachment::query()->firstOrFail();

    Storage::disk('public')->assertExists($attachment->path);

    $downloadResponse = $this->actingAs($dispatcher)
        ->get("/crm/attachments/{$attachment->id}/download")
        ->assertOk();

    expect($downloadResponse->headers->get('content-disposition'))
        ->toContain('attachment;')
        ->toContain('investigation-note.txt');
});

test('regular internal staff cannot manage threads assigned to another user', function () {
    Storage::fake('public');

    $dispatcher = makeInternalUser();
    $otherInternalUser = makeInternalUser();
    $superAdmin = makeSuperAdminUser();
    $company = makeCompany($superAdmin);
    $externalUser = makeExternalUser($company);
    $thread = makeThread($externalUser, $otherInternalUser);

    $this->actingAs($dispatcher)
        ->patchJson("/crm/threads/{$thread->id}/close")
        ->assertForbidden();

    $this->actingAs($dispatcher)
        ->postJson("/crm/threads/{$thread->id}/messages", [
            'body' => 'Trying to respond outside my queue.',
            'is_internal' => true,
        ])
        ->assertForbidden();

    $message = $thread->messages()->create([
        'sender_user_id' => $otherInternalUser->id,
        'body' => 'Existing message',
        'is_internal' => true,
    ]);

    $this->actingAs($dispatcher)
        ->post("/crm/threads/{$thread->id}/messages/{$message->id}/attachments", [
            'file' => UploadedFile::fake()->create('forbidden.txt', 4, 'text/plain'),
        ])
        ->assertForbidden();
});
