<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanyProfileChangeRequest;
use App\Models\CrmMessage;
use App\Models\CrmMessageAttachment;
use App\Models\CrmThread;
use App\Models\Dispatch;
use App\Models\DispatchChangeRequest;
use App\Models\RouteFavorite;
use App\Models\RouteSearchLog;
use App\Models\User;
use Database\Seeders\Concerns\CreatesSeedPdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DevelopmentSupportingDataSeeder extends Seeder
{
    use CreatesSeedPdf;

    public function run(): void
    {
        $company = Company::query()->where('company_code', 'NORTHSTAR')->first();
        $operator = User::query()->where('username', 'northstar-operator')->first();
        $administrator = User::query()->where('username', '2026-0002')->first();
        $commuter = User::query()->where('username', 'janrey_u')->first();
        $dispatch = Dispatch::query()->where('status', 'arrived')->first();

        if ($company === null || $operator === null || $administrator === null || $commuter === null || $dispatch === null) {
            return;
        }

        $this->seedDispatchChangeRequest($dispatch, $operator, $administrator);
        $this->seedProfileChangeRequest($company, $operator, $administrator);
        $this->seedCrmConversation($company, $operator, $administrator);
        $this->seedRouteActivity($commuter);
        $this->seedAuditLog($company, $administrator);
    }

    private function seedDispatchChangeRequest(Dispatch $dispatch, User $operator, User $administrator): void
    {
        DispatchChangeRequest::withTrashed()->updateOrCreate(
            ['dispatch_id' => $dispatch->id, 'requested_by' => $operator->id, 'requested_field' => DispatchChangeRequest::FIELD_PAX_COUNT],
            [
                'old_value' => $dispatch->pax_count,
                'requested_value' => 32,
                'reason' => 'Development fixture for internal dispatch review.',
                'status' => DispatchChangeRequest::STATUS_REJECTED,
                'approved_by' => $administrator->id,
                'rejection_reason' => 'Use the verified passenger count before departure.',
                'approved_at' => now()->subHour(),
            ],
        );
    }

    private function seedProfileChangeRequest(Company $company, User $operator, User $administrator): void
    {
        CompanyProfileChangeRequest::withTrashed()->updateOrCreate(
            ['company_id' => $company->id, 'requested_by' => $operator->id, 'status' => CompanyProfileChangeRequest::STATUS_PENDING],
            [
                'requested_values' => ['company_phone' => '+639170099999'],
                'current_values' => ['company_phone' => $company->company_phone],
                'approved_by' => null,
                'rejection_reason' => null,
                'approved_at' => null,
            ],
        );

        CompanyProfileChangeRequest::withTrashed()->updateOrCreate(
            ['company_id' => $company->id, 'requested_by' => $operator->id, 'status' => CompanyProfileChangeRequest::STATUS_REJECTED],
            [
                'requested_values' => ['authorized_representative_position' => 'Unverified Role'],
                'current_values' => ['authorized_representative_position' => $company->authorized_representative_position],
                'approved_by' => $administrator->id,
                'rejection_reason' => 'Supporting evidence is required for this profile update.',
                'approved_at' => now()->subDay(),
            ],
        );
    }

    private function seedCrmConversation(Company $company, User $operator, User $administrator): void
    {
        $thread = CrmThread::query()->updateOrCreate(
            ['company_id' => $company->id, 'subject' => 'Development compliance inquiry'],
            [
                'created_by_user_id' => $operator->id,
                'assigned_to_user_id' => $administrator->id,
                'category' => 'terminal_operations',
                'is_closed' => false,
                'closed_at' => null,
                'last_message_at' => now()->subMinutes(30),
                'details' => ['fixture' => true],
            ],
        );

        $externalMessage = CrmMessage::query()->updateOrCreate(
            ['thread_id' => $thread->id, 'sender_user_id' => $operator->id, 'body' => 'Please review the sample compliance documents.'],
            ['is_internal' => false],
        );

        CrmMessage::query()->updateOrCreate(
            ['thread_id' => $thread->id, 'sender_user_id' => $administrator->id, 'body' => 'Development note: review is pending.'],
            ['is_internal' => true],
        );

        $path = "seed-fixtures/crm/{$thread->id}/development-compliance.pdf";
        if (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, $this->seedPdf(['SAMPLE DEVELOPMENT CRM ATTACHMENT']));
        }

        CrmMessageAttachment::query()->updateOrCreate(
            ['message_id' => $externalMessage->id, 'original_name' => 'development-compliance.pdf'],
            [
                'thread_id' => $thread->id,
                'uploaded_by_user_id' => $operator->id,
                'disk' => 'public',
                'path' => $path,
                'mime_type' => 'application/pdf',
                'size_bytes' => Storage::disk('public')->size($path),
            ],
        );
    }

    private function seedRouteActivity(User $commuter): void
    {
        RouteFavorite::query()->updateOrCreate(
            ['user_id' => $commuter->id, 'origin' => 'PITX', 'destination' => 'Trece Martires'],
        );

        RouteSearchLog::query()->firstOrCreate(
            ['user_id' => $commuter->id, 'origin' => 'PITX', 'destination' => 'Trece Martires'],
        );
    }

    private function seedAuditLog(Company $company, User $administrator): void
    {
        AuditLog::query()->firstOrCreate(
            [
                'user_id' => $administrator->id,
                'action' => 'development.seeded',
                'auditable_type' => Company::class,
                'auditable_id' => $company->id,
            ],
            [
                'company_id' => $company->id,
                'changed_fields' => [],
                'metadata' => ['fixture' => true],
                'request_method' => 'SEED',
                'request_url' => 'artisan:db:seed',
            ],
        );
    }
}
