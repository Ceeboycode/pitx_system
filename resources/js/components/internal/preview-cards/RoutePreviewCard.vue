<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { PreviewCard, PreviewCardRow } from '@/components/ui/_preview-card';

type Route = {
    id: number;
    route_name: string;
    status: 'active' | 'inactive' | null;
    gate?: { gate_name?: string | null } | null;
    created_at_human?: string | null;
    deleted_at_human?: string | null;
    deleter?: { name?: string | null } | null;
};

const props = defineProps<{
    route: Route;
    archived?: boolean;
}>();

defineEmits<{ close: [] }>();

function statusClass(status: Route['status']): string {
    return status === 'active'
        ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
        : 'bg-slate-100 text-slate-500 border-0';
}

function statusDot(status: Route['status']): string {
    return status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400';
}
</script>

<template>
    <PreviewCard
        :title="props.route.route_name"
        :description="props.archived ? 'Archived preview' : 'Preview'"
        title-class="capitalize"
        @close="$emit('close')"
    >
        <div class="space-y-3 pt-2">
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Status</span>
                <Badge :class="['gap-1.5', statusClass(route.status)]">
                    <span :class="['h-1.5 w-1.5 rounded-full', statusDot(route.status)]" />
                    {{ route.status === 'active' ? 'Active' : 'Inactive' }}
                </Badge>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Gate</span>
                <span class="text-right text-sm">{{ route.gate?.gate_name || 'Not assigned' }}</span>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Created</span>
                <span class="text-right text-sm">{{ route.created_at_human || 'Not recorded' }}</span>
            </div>
        </div>

        <div v-if="props.archived" class="space-y-3 pt-1">
            <PreviewCardRow label="Archived">{{ props.route.deleted_at_human || '—' }}</PreviewCardRow>
            <PreviewCardRow v-if="props.route.deleter !== undefined" label="Archived By">{{ props.route.deleter?.name || '—' }}</PreviewCardRow>
        </div>
    </PreviewCard>
</template>
