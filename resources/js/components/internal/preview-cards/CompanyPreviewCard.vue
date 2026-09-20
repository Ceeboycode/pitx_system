<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { PreviewCard, PreviewCardRow } from '@/components/ui/_preview-card';
import { RiImageAddLine } from 'vue-remix-icons';

type CompanyStatus =
    | 'draft'
    | 'docs_completed'
    | 'for_verification'
    | 'verified'
    | 'needs_revision'
    | 'rejected'
    | null
    | undefined;

type Company = {
    id: number;
    company_name: string;
    company_code: string;
    company_email?: string | null;
    company_email_verified_at?: string | null;
    company_phone?: string | null;
    business_type?: string | null;
    logo_url?: string | null;
    status?: CompanyStatus;
    is_active?: boolean | number | null;
    created_at_human?: string | null;
    deleted_at_human?: string | null;
    deleter?: { name?: string | null } | null;
};

const props = defineProps<{
    company: Company;
    archived?: boolean;
}>();

defineEmits<{ close: [] }>();

function humanizeStatus(status?: CompanyStatus): string {
    if (!status) return '—';
    const map: Record<Exclude<CompanyStatus, null | undefined>, string> = {
        draft: 'Draft',
        docs_completed: 'Docs Completed',
        for_verification: 'For Verification',
        verified: 'Verified',
        needs_revision: 'Needs Revision',
        rejected: 'Rejected',
    };
    return map[status] ?? status.replace(/_/g, ' ');
}

function statusClass(status?: CompanyStatus): string {
    switch (status) {
        case 'verified':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'docs_completed':
            return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'for_verification':
            return 'bg-violet-100 text-violet-700 border-violet-200';
        case 'needs_revision':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'rejected':
            return 'bg-rose-100 text-rose-600 border-rose-200';
        default:
            return 'bg-slate-100 text-slate-500 border-0';
    }
}

function statusDot(status?: CompanyStatus): string {
    switch (status) {
        case 'verified':
            return 'bg-emerald-500';
        case 'docs_completed':
            return 'bg-blue-500';
        case 'for_verification':
            return 'bg-violet-500';
        case 'needs_revision':
            return 'bg-amber-500';
        case 'rejected':
            return 'bg-rose-500';
        default:
            return 'bg-slate-400';
    }
}

function isCompanyActive(company: Company): boolean {
    return company.is_active === true || company.is_active === 1;
}

function activeStatusClass(company: Company): string {
    return isCompanyActive(company)
        ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
        : 'bg-slate-100 text-slate-500 border-0';
}

function activeStatusDot(company: Company): string {
    return isCompanyActive(company) ? 'bg-emerald-500' : 'bg-slate-400';
}

function activeStatusLabel(company: Company): string {
    return isCompanyActive(company) ? 'Active' : 'Inactive';
}

function hasVerifiedEmail(company: Company): boolean {
    return !!company.company_email_verified_at;
}
</script>

<template>
    <PreviewCard
        :title="props.company.company_name"
        :description="props.archived ? 'Archived preview' : 'Preview'"
        title-class="capitalize"
        @close="$emit('close')"
    >
        <div class="flex aspect-4/3 items-center justify-center overflow-hidden rounded-md border border-dashed border-custom-bg-dark bg-custom-bg text-custom-shadow/70 dark:border-none dark:bg-custom-bg-dark">
            <img
                v-if="company.logo_url"
                :src="company.logo_url"
                :alt="`${company.company_name} logo`"
                class="h-full w-full object-contain"
            />
            <div v-else class="flex flex-col items-center gap-1 text-center">
                <RiImageAddLine class="h-6 w-6" />
                <span class="text-sm">No company logo</span>
            </div>
        </div>

        <div class="space-y-3 pt-2">
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Verification Status</span>
                <Badge :class="['gap-1.5', statusClass(company.status ?? null)]">
                    <span :class="['h-1.5 w-1.5 rounded-full', statusDot(company.status ?? null)]" />
                    {{ humanizeStatus(company.status ?? null) }}
                </Badge>
            </div>
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Active Status</span>
                <Badge :class="['gap-1.5', activeStatusClass(company)]">
                    <span :class="['h-1.5 w-1.5 rounded-full', activeStatusDot(company)]" />
                    {{ activeStatusLabel(company) }}
                </Badge>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Company Code</span>
                <span class="rounded bg-custom-bg px-2 py-0.5 font-mono text-xs font-semibold text-custom-shadow dark:bg-custom-bg-light">{{ company.company_code }}</span>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Business Type</span>
                <span class="text-right text-sm capitalize">{{ company.business_type || 'Not recorded' }}</span>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Email</span>
                <div class="min-w-0 text-right">
                    <p class="truncate text-sm">{{ company.company_email || 'Not recorded' }}</p>
                    <p v-if="company.company_email" class="text-xs text-custom-shadow/70">
                        {{ hasVerifiedEmail(company) ? 'Verified' : 'Not verified' }}
                    </p>
                </div>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Phone</span>
                <span class="text-right text-sm">{{ company.company_phone || 'Not recorded' }}</span>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Created</span>
                <span class="text-right text-sm">{{ company.created_at_human || 'Not recorded' }}</span>
            </div>
        </div>

        <div v-if="props.archived" class="space-y-3 pt-1">
            <PreviewCardRow label="Archived">{{ props.company.deleted_at_human || '—' }}</PreviewCardRow>
            <PreviewCardRow v-if="props.company.deleter !== undefined" label="Archived By">{{ props.company.deleter?.name || '—' }}</PreviewCardRow>
        </div>
    </PreviewCard>
</template>
