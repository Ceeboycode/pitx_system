<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { PreviewCard, PreviewCardRow } from '@/components/ui/_preview-card';

type VehicleType = {
    id: number;
    type_name: string;
    is_active: boolean;
    deleted_at_human?: string | null;
    deleter?: { name?: string | null } | null;
};

const props = defineProps<{
    vehicleType: VehicleType;
    archived?: boolean;
}>();

defineEmits<{ close: [] }>();
</script>

<template>
    <PreviewCard
        :title="props.vehicleType.type_name"
        :description="props.archived ? 'Archived preview' : 'Preview'"
        title-class="capitalize"
        @close="$emit('close')"
    >
        <div class="space-y-3 pt-2">
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Status</span>
                <Badge :class="['gap-1.5', vehicleType.is_active ? 'border-emerald-200 bg-emerald-100 text-emerald-700' : 'border-0 bg-slate-100 text-slate-500']">
                    <span :class="['h-1.5 w-1.5 rounded-full', vehicleType.is_active ? 'bg-emerald-500' : 'bg-slate-400']" />
                    {{ vehicleType.is_active ? 'Active' : 'Inactive' }}
                </Badge>
            </div>
        </div>

        <div v-if="props.archived" class="space-y-3 pt-1">
            <PreviewCardRow label="Archived">{{ props.vehicleType.deleted_at_human || '—' }}</PreviewCardRow>
            <PreviewCardRow v-if="props.vehicleType.deleter !== undefined" label="Archived By">{{ props.vehicleType.deleter?.name || '—' }}</PreviewCardRow>
        </div>
    </PreviewCard>
</template>
