<?php

use App\Models\CrmThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function internalRole(string $name): Role
{
    return Role::firstOrCreate(
        ['name' => $name, 'guard_name' => 'web'],
        ['type' => 'internal']
    );
}

test('super admin can assign crm thread to admin user', function () {
    internalRole('super-admin');
    internalRole('admin');

    $superAdmin = User::factory()->create();
    $admin = User::factory()->create();
    $creator = User::factory()->create();

    $superAdmin->assignRole('super-admin');
    $admin->assignRole('admin');

    $thread = CrmThread::query()->create([
        'company_id' => null,
        'created_by_user_id' => $creator->id,
        'category' => 'system',
        'subject' => 'Need support on schedule updates',
        'last_message_at' => now(),
    ]);

    $this->actingAs($superAdmin)
        ->patchJson("/crm/threads/{$thread->id}/assign", [
            'assigned_to_user_id' => $admin->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.assigned_to_user_id', $admin->id);

    expect($thread->fresh()->assigned_to_user_id)->toBe($admin->id);
});

test('non super admin internal users cannot assign crm thread', function () {
    internalRole('super-admin');
    internalRole('admin');

    $admin = User::factory()->create();
    $assignee = User::factory()->create();
    $creator = User::factory()->create();

    $admin->assignRole('admin');
    $assignee->assignRole('admin');

    $thread = CrmThread::query()->create([
        'company_id' => null,
        'created_by_user_id' => $creator->id,
        'category' => 'compliance',
        'subject' => 'Pending compliance review',
        'last_message_at' => now(),
    ]);

    $this->actingAs($admin)
        ->patchJson("/crm/threads/{$thread->id}/assign", [
            'assigned_to_user_id' => $assignee->id,
        ])
        ->assertForbidden();
});

test('super admin cannot assign crm thread to non admin users', function () {
    internalRole('super-admin');
    internalRole('admin');

    $superAdmin = User::factory()->create();
    $nonAdmin = User::factory()->create();
    $creator = User::factory()->create();

    $superAdmin->assignRole('super-admin');

    $thread = CrmThread::query()->create([
        'company_id' => null,
        'created_by_user_id' => $creator->id,
        'category' => 'system',
        'subject' => 'Error in route visibility',
        'last_message_at' => now(),
    ]);

    $this->actingAs($superAdmin)
        ->patchJson("/crm/threads/{$thread->id}/assign", [
            'assigned_to_user_id' => $nonAdmin->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['assigned_to_user_id']);

    expect($thread->fresh()->assigned_to_user_id)->toBeNull();
});

test('admin only sees threads assigned to them in crm thread index', function () {
    internalRole('admin');

    $admin = User::factory()->create();
    $otherAdmin = User::factory()->create();
    $creator = User::factory()->create();

    $admin->assignRole('admin');
    $otherAdmin->assignRole('admin');

    $assignedThread = CrmThread::query()->create([
        'company_id' => null,
        'created_by_user_id' => $creator->id,
        'assigned_to_user_id' => $admin->id,
        'category' => 'system',
        'subject' => 'Assigned to admin',
        'last_message_at' => now()->subMinute(),
    ]);

    $otherAdminsThread = CrmThread::query()->create([
        'company_id' => null,
        'created_by_user_id' => $creator->id,
        'assigned_to_user_id' => $otherAdmin->id,
        'category' => 'system',
        'subject' => 'Assigned to other admin',
        'last_message_at' => now()->subMinutes(2),
    ]);

    $unassignedThread = CrmThread::query()->create([
        'company_id' => null,
        'created_by_user_id' => $creator->id,
        'assigned_to_user_id' => null,
        'category' => 'compliance',
        'subject' => 'Unassigned thread',
        'last_message_at' => now()->subMinutes(3),
    ]);

    $this->actingAs($admin)
        ->get(route('crm.threads.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Crm/Threads/Index')
            ->where('threads.data', function ($threads) use ($admin, $assignedThread, $otherAdminsThread, $unassignedThread): bool {
                $ids = collect($threads)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $allAssignedToCurrentAdmin = collect($threads)
                    ->every(fn (array $thread): bool => (int) ($thread['assigned_to_user_id'] ?? 0) === $admin->id);

                return in_array($assignedThread->id, $ids, true)
                    && ! in_array($otherAdminsThread->id, $ids, true)
                    && ! in_array($unassignedThread->id, $ids, true)
                    && $allAssignedToCurrentAdmin;
            })
        );
});

test('admin can view crm thread assigned to them', function () {
    internalRole('admin');

    $admin = User::factory()->create();
    $creator = User::factory()->create();

    $admin->assignRole('admin');

    $thread = CrmThread::query()->create([
        'company_id' => null,
        'created_by_user_id' => $creator->id,
        'assigned_to_user_id' => $admin->id,
        'category' => 'system',
        'subject' => 'Assigned thread',
        'last_message_at' => now(),
    ]);

    $this->actingAs($admin)
        ->getJson(route('crm.threads.show', $thread))
        ->assertOk()
        ->assertJsonPath('data.id', $thread->id);
});

test('admin cannot view crm thread not assigned to them', function () {
    internalRole('admin');

    $admin = User::factory()->create();
    $otherAdmin = User::factory()->create();
    $creator = User::factory()->create();

    $admin->assignRole('admin');
    $otherAdmin->assignRole('admin');

    $thread = CrmThread::query()->create([
        'company_id' => null,
        'created_by_user_id' => $creator->id,
        'assigned_to_user_id' => $otherAdmin->id,
        'category' => 'compliance',
        'subject' => 'Other admin thread',
        'last_message_at' => now(),
    ]);

    $this->actingAs($admin)
        ->getJson(route('crm.threads.show', $thread))
        ->assertForbidden();
});

test('admin can send message only to crm thread assigned to them', function () {
    internalRole('admin');

    $admin = User::factory()->create();
    $otherAdmin = User::factory()->create();
    $creator = User::factory()->create();

    $admin->assignRole('admin');
    $otherAdmin->assignRole('admin');

    $assignedThread = CrmThread::query()->create([
        'company_id' => null,
        'created_by_user_id' => $creator->id,
        'assigned_to_user_id' => $admin->id,
        'category' => 'system',
        'subject' => 'Assigned thread',
        'last_message_at' => now(),
    ]);

    $otherThread = CrmThread::query()->create([
        'company_id' => null,
        'created_by_user_id' => $creator->id,
        'assigned_to_user_id' => $otherAdmin->id,
        'category' => 'system',
        'subject' => 'Other thread',
        'last_message_at' => now(),
    ]);

    $this->actingAs($admin)
        ->postJson(route('crm.threads.messages.store', $assignedThread), [
            'body' => 'Follow-up update from assigned admin.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.thread_id', $assignedThread->id)
        ->assertJsonPath('data.sender_user_id', $admin->id);

    $this->assertDatabaseHas('crm_messages', [
        'thread_id' => $assignedThread->id,
        'sender_user_id' => $admin->id,
        'body' => 'Follow-up update from assigned admin.',
    ]);

    $this->actingAs($admin)
        ->postJson(route('crm.threads.messages.store', $otherThread), [
            'body' => 'Trying to send on unassigned thread.',
        ])
        ->assertForbidden();
});
