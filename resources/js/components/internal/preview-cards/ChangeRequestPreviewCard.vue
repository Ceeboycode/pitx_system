<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { PreviewCard, PreviewCardRow } from '@/components/ui/_preview-card';
import { RiEyeLine } from 'vue-remix-icons';

type SupportingDocument = {
    doc_type?: string | null;
    original_name?: string | null;
    mime_type?: string | null;
    preview_url?: string | null;
};

type ChangeRequest = {
    id: number;
    status: 'pending' | 'approved' | 'rejected';
    requested_values: Record<string, unknown>;
    current_values: Record<string, unknown> | null;
    logo_change?: {
        has_change: boolean;
        new_preview_url?: string | null;
        old_preview_url?: string | null;
        is_remove?: boolean;
    };
    supporting_documents?: SupportingDocument[];
    company: { company_name: string };
    requester: { name: string; email: string | null } | null;
};

const props = defineProps<{
    request: ChangeRequest;
}>();

const emit = defineEmits<{
    close: [];
    'preview-logo': [];
    'preview-document': [doc: SupportingDocument];
}>();

const requestedFieldCount = computed(() => Object.keys(props.request.requested_values).length);

const hasPrimaryPreview = computed(() => {
    const logo = props.request.logo_change?.new_preview_url ?? props.request.logo_change?.old_preview_url ?? null;

    return !!logo || (props.request.supporting_documents ?? []).some((doc) => !!doc.preview_url);
});

function badgeVariant(status: ChangeRequest['status']): 'success' | 'warning' | 'destructive' {
    if (status === 'approved') return 'success';
    if (status === 'rejected') return 'destructive';
    return 'warning';
}

function normalizeFieldName(field: string): string {
    return field.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function humanize(value: string | null | undefined): string {
    if (!value) return '—';
    return normalizeFieldName(value);
}

function formatValue(value: unknown): string {
    if (value === null || value === undefined || value === '') return '—';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (Array.isArray(value) || typeof value === 'object') return JSON.stringify(value);
    return String(value);
}
</script>

<template>
    <PreviewCard :title="props.request.company.company_name" description="Request preview" @close="emit('close')">
        <div class="space-y-4">
            <PreviewCardRow label="Status" class="items-center">
                <Badge :variant="badgeVariant(props.request.status)" class="capitalize">{{ props.request.status }}</Badge>
            </PreviewCardRow>
            <PreviewCardRow label="Requester">
                <p class="truncate text-sm text-custom-shadow/80">{{ props.request.requester?.name ?? '—' }}</p>
                <p class="truncate text-xs text-custom-shadow/60">{{ props.request.requester?.email ?? '—' }}</p>
            </PreviewCardRow>

            <hr class="my-4 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light" />

            <div class="space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-semibold text-custom-shadow">Requested changes</span>
                    <span class="text-sm text-custom-shadow/80">{{ requestedFieldCount }}</span>
                </div>
                <div v-if="requestedFieldCount" class="space-y-2">
                    <div
                        v-for="(value, field) in props.request.requested_values"
                        :key="`${props.request.id}-${field}`"
                        class="rounded-md bg-custom-bg px-3 py-2 dark:bg-custom-bg-dark"
                    >
                        <p class="text-sm font-medium text-custom-shadow">{{ normalizeFieldName(String(field)) }}</p>
                        <p class="break-words text-xs text-custom-shadow/70">{{ formatValue(props.request.current_values?.[field]) }} → {{ formatValue(value) }}</p>
                    </div>
                </div>
                <p v-else class="rounded-md bg-custom-bg px-3 py-2 text-sm text-custom-shadow/70 dark:bg-custom-bg-dark">No profile field changes requested.</p>
            </div>

            <div v-if="props.request.logo_change?.has_change" class="rounded-md bg-custom-bg px-3 py-2 dark:bg-custom-bg-dark">
                <p class="text-sm font-medium text-custom-shadow">Company Logo</p>
                <p class="text-xs text-custom-shadow/70">{{ props.request.logo_change.is_remove ? 'Remove current logo' : 'Replace current logo' }}</p>
                <Button v-if="hasPrimaryPreview" variant="link" size="sm" class="h-auto px-0" @click="emit('preview-logo')">Preview logo</Button>
            </div>

            <div v-if="props.request.supporting_documents?.length" class="space-y-2">
                <span class="text-sm font-semibold text-custom-shadow">Supporting documents</span>
                <button
                    v-for="(doc, index) in props.request.supporting_documents"
                    :key="index"
                    type="button"
                    class="flex w-full items-center justify-between rounded-md bg-custom-bg px-3 py-2 text-left dark:bg-custom-bg-dark"
                    @click="doc.preview_url && emit('preview-document', doc)"
                >
                    <span class="min-w-0 truncate text-sm">{{ doc.original_name ?? humanize(doc.doc_type) }}</span>
                    <RiEyeLine v-if="doc.preview_url" class="h-4 w-4 shrink-0" />
                </button>
            </div>
        </div>
    </PreviewCard>
</template>
