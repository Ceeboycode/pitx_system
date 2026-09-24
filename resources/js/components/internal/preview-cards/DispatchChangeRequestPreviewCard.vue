<script setup lang="ts">
import { InputMessage } from '@/components/ui/_input-message';
import { Badge } from '@/components/ui/badge';
import { PreviewCard, PreviewCardRow } from '@/components/ui/_preview-card';

type DispatchChangeRequest = {
    id: number;
    requested_by?: { name: string; email: string | null } | null;
    company_name: string;
    requested_field: string;
    field_label?: string | null;
    old_value_display?: string | null;
    requested_value_display?: string | null;
    reason: string;
    status: string;
    rejected_by?: { name: string } | null;
    rejection_reason?: string | null;
    approved_at?: string | null;
    created_at?: string | null;
    dispatch?: {
        id: number;
        plate_number: string;
        status: string;
        driver?: { name: string } | null;
        gate?: { gate_name: string } | null;
        bay_number?: string | number | null;
    } | null;
};

const props = defineProps<{
    request: DispatchChangeRequest;
}>();

defineEmits<{ close: [] }>();

function humanize(value: string | null | undefined): string {
    if (!value) return '—';
    return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function statusClass(status: string): string {
    switch (status) {
        case 'approved':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'rejected':
            return 'bg-rose-100 text-rose-600 border-rose-200';
        default:
            return 'bg-amber-100 text-amber-700 border-amber-200';
    }
}
</script>

<template>
    <PreviewCard
        :title="props.request.dispatch?.plate_number || `Dispatch #${props.request.id}`"
        description="Change request preview"
        title-class="uppercase"
        @close="$emit('close')"
    >
        <div class="space-y-3 pt-2">
            <PreviewCardRow label="Status" class="items-center">
                <Badge :class="['capitalize', statusClass(props.request.status)]">{{ props.request.status }}</Badge>
            </PreviewCardRow>
            <PreviewCardRow label="Company">{{ props.request.company_name || '—' }}</PreviewCardRow>
            <PreviewCardRow label="Requested By">
                <p class="truncate text-sm text-custom-shadow/80">{{ props.request.requested_by?.name ?? '—' }}</p>
                <p class="truncate text-xs text-custom-shadow/60">{{ props.request.requested_by?.email ?? '—' }}</p>
            </PreviewCardRow>
            <PreviewCardRow label="Requested At">{{ props.request.created_at ?? '—' }}</PreviewCardRow>

            <hr class="my-4 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light" />

            <div class="space-y-2">
                <span class="text-sm font-semibold text-custom-shadow">Requested change</span>
                <div class="rounded-md bg-custom-bg px-3 py-2 dark:bg-custom-bg-dark">
                    <p class="text-sm font-semibold text-custom-shadow">{{ props.request.field_label ?? humanize(props.request.requested_field) }}</p>
                    <p class="break-words text-xs text-custom-shadow/70">
                        {{ props.request.old_value_display ?? '—' }} → {{ props.request.requested_value_display ?? '—' }}
                    </p>
                </div>
            </div>

            <div class="space-y-1">
                <span class="text-sm font-semibold text-custom-shadow">Reason</span>
                <InputMessage class="mt-0" :message="props.request.reason || '—'" />
            </div>

            <div v-if="props.request.rejection_reason" class="space-y-1">
                <span class="text-sm font-semibold text-custom-shadow">Rejection Reason</span>
                <InputMessage class="mt-0" :message="props.request.rejection_reason ?? undefined" />
                <p v-if="props.request.rejected_by" class="text-xs text-custom-shadow/60">Rejected by {{ props.request.rejected_by.name }}</p>
            </div>

            <template v-if="props.request.dispatch">
                <hr class="my-4 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light" />
                <PreviewCardRow label="Dispatch Status">{{ humanize(props.request.dispatch.status) }}</PreviewCardRow>
                <PreviewCardRow label="Driver">{{ props.request.dispatch.driver?.name || 'Not recorded' }}</PreviewCardRow>
                <PreviewCardRow label="Gate">{{ props.request.dispatch.gate?.gate_name || 'Not assigned' }}</PreviewCardRow>
                <PreviewCardRow label="Bay">{{ props.request.dispatch.bay_number || 'Not assigned' }}</PreviewCardRow>
            </template>
        </div>
    </PreviewCard>
</template>
