<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { PreviewCard } from '@/components/ui/_preview-card';

type ChangeLine = {
    field: string;
    label: string;
    old: unknown;
    new: unknown;
};

type Log = {
    id: number;
    action: string;
    action_label: string;
    entity_label: string;
    entity_name: string | null;
    changes: ChangeLine[];
    ip_address: string | null;
    request_method: string | null;
    request_url: string | null;
    created_at: string | null;
    created_at_human: string | null;
};

const props = defineProps<{
    log: Log;
}>();

defineEmits<{ close: [] }>();

function formatValue(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }

    if (Array.isArray(value) || typeof value === 'object') {
        return JSON.stringify(value);
    }

    return String(value);
}

function actionBadgeClass(action: string): string {
    if (action === 'created')
        return 'border-emerald-200 bg-emerald-100 text-emerald-700';
    if (action === 'updated')
        return 'border-blue-200 bg-blue-100 text-blue-700';
    if (action === 'deleted')
        return 'border-rose-200 bg-rose-100 text-rose-700';
    if (action.startsWith('auth.'))
        return 'border-amber-200 bg-amber-100 text-amber-700';

    return 'border-slate-200 bg-slate-100 text-slate-600';
}
</script>

<template>
    <PreviewCard :title="props.log.action_label" @close="$emit('close')">
        <div class="flex items-center justify-between gap-3">
            <span class="text-sm font-semibold text-custom-shadow">Action</span>
            <Badge :class="actionBadgeClass(log.action)">
                {{ log.action_label }}
            </Badge>
        </div>
        <div class="flex items-start justify-between gap-3">
            <span class="text-sm font-semibold text-custom-shadow">Entity</span>
            <div class="min-w-0 text-right">
                <p class="truncate text-sm text-custom-shadow/80">{{ log.entity_label }}</p>
                <p class="truncate text-xs text-custom-shadow/60">{{ log.entity_name ?? '—' }}</p>
            </div>
        </div>
        <div class="flex items-start justify-between gap-3">
            <span class="text-sm font-semibold text-custom-shadow">Timestamp</span>
            <div class="text-right">
                <p class="text-sm text-custom-shadow/80">{{ log.created_at_human ?? '—' }}</p>
                <p class="text-xs text-custom-shadow/60">{{ log.created_at ?? '—' }}</p>
            </div>
        </div>

        <hr class="my-4 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">

        <div class="space-y-2">
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Changed fields</span>
                <span class="text-sm text-custom-shadow/80">{{ log.changes.length }}</span>
            </div>
            <div v-if="log.changes.length" class="space-y-2">
                <div
                    v-for="change in log.changes"
                    :key="`${log.id}-preview-${change.field}`"
                    class="rounded-md bg-custom-bg px-3 py-2 dark:bg-custom-bg-dark"
                >
                    <p class="text-sm font-medium text-custom-shadow">{{ change.label }}</p>
                    <p class="break-words text-xs text-custom-shadow/70">
                        {{ formatValue(change.old) }} → {{ formatValue(change.new) }}
                    </p>
                </div>
            </div>
            <p v-else class="rounded-md bg-custom-bg px-3 py-2 text-sm text-custom-shadow/70 dark:bg-custom-bg-dark">
                No field-level changes recorded.
            </p>
        </div>

        <hr class="my-4 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">

        <div class="space-y-2 text-sm text-custom-shadow/80">
            <div class="flex justify-between gap-3">
                <span class="font-semibold text-custom-shadow">IP</span>
                <span>{{ log.ip_address ?? '—' }}</span>
            </div>
            <div class="flex justify-between gap-3">
                <span class="font-semibold text-custom-shadow">Method</span>
                <span>{{ log.request_method ?? '—' }}</span>
            </div>
            <div class="space-y-1">
                <span class="font-semibold text-custom-shadow">URL</span>
                <p class="break-all text-xs">{{ log.request_url ?? '—' }}</p>
            </div>
        </div>
    </PreviewCard>
</template>
