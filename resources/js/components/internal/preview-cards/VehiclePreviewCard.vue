<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { PreviewCard, PreviewCardRow } from '@/components/ui/_preview-card';
import {
    operationalStatusClass,
    operationalStatusDot,
    operationalStatusLabel,
    verificationStatusClass,
    verificationStatusDot,
    verificationStatusLabel,
} from '@/lib/vehicle-status';
import { RiBusLine } from 'vue-remix-icons';

type Vehicle = {
    id: number;
    plate_number?: string | null;
    body_number?: string | null;
    capacity?: string | number | null;
    status?: string | null;
    verification_status?: string | null;
    operator_remark?: string | null;
    suspension_remark?: string | null;
    verification_remark?: string | null;
    company?: { company_name?: string | null } | null;
    route?: { route_name?: string | null } | null;
    vehicle_type?: { type_name?: string | null } | null;
    deleted_at_human?: string | null;
    deleter?: { name?: string | null } | null;
};

const props = defineProps<{
    vehicle: Vehicle;
    archived?: boolean;
}>();

defineEmits<{ close: [] }>();
</script>

<template>
    <PreviewCard
        :title="props.vehicle.plate_number || 'Vehicle'"
        :description="props.archived ? 'Archived preview' : 'Preview'"
        title-class="uppercase"
        @close="$emit('close')"
    >
        <div class="flex aspect-4/3 items-center justify-center rounded-md border border-dashed border-custom-bg-dark bg-custom-bg text-custom-shadow/70 dark:border-none dark:bg-custom-bg-dark">
            <RiBusLine class="h-16 w-16" />
        </div>

        <div class="space-y-3 pt-2">
            <PreviewCardRow v-if="props.vehicle.status" label="Operational Status" class="items-center">
                <Badge :class="['gap-1.5', operationalStatusClass(props.vehicle.status)]">
                    <span :class="['h-1.5 w-1.5 rounded-full', operationalStatusDot(props.vehicle.status)]" />
                    {{ operationalStatusLabel(props.vehicle.status) }}
                </Badge>
            </PreviewCardRow>
            <PreviewCardRow v-if="props.vehicle.verification_status" label="Verification Status" class="items-center">
                <Badge :class="['gap-1.5', verificationStatusClass(props.vehicle.verification_status)]">
                    <span :class="['h-1.5 w-1.5 rounded-full', verificationStatusDot(props.vehicle.verification_status)]" />
                    {{ verificationStatusLabel(props.vehicle.verification_status) }}
                </Badge>
            </PreviewCardRow>
            <PreviewCardRow label="Company">{{ props.vehicle.company?.company_name || 'Not assigned' }}</PreviewCardRow>
            <PreviewCardRow label="Route">{{ props.vehicle.route?.route_name || 'Not assigned' }}</PreviewCardRow>
            <PreviewCardRow label="Vehicle Type">{{ props.vehicle.vehicle_type?.type_name ?? '—' }}</PreviewCardRow>
            <PreviewCardRow label="Body Number">{{ props.vehicle.body_number || 'Not recorded' }}</PreviewCardRow>
            <PreviewCardRow label="Capacity">{{ props.vehicle.capacity || 'Not recorded' }}</PreviewCardRow>
            <template v-if="props.archived">
                <PreviewCardRow label="Archived">{{ props.vehicle.deleted_at_human || '—' }}</PreviewCardRow>
                <PreviewCardRow v-if="props.vehicle.deleter !== undefined" label="Archived By">{{ props.vehicle.deleter?.name || '—' }}</PreviewCardRow>
            </template>

            <div v-if="props.vehicle.operator_remark" class="space-y-1">
                <span class="text-sm font-semibold text-custom-shadow">Operator Remark</span>
                <p class="rounded-md bg-custom-bg p-3 text-sm text-custom-shadow/80 dark:bg-custom-bg-dark">{{ props.vehicle.operator_remark }}</p>
            </div>
            <div v-if="props.vehicle.suspension_remark" class="space-y-1">
                <span class="text-sm font-semibold text-custom-shadow">Admin Remark</span>
                <p class="rounded-md bg-custom-bg p-3 text-sm text-custom-shadow/80 dark:bg-custom-bg-dark">{{ props.vehicle.suspension_remark }}</p>
            </div>
            <div v-if="props.vehicle.verification_remark" class="space-y-1">
                <span class="text-sm font-semibold text-custom-shadow">Verification Remark</span>
                <p class="rounded-md bg-custom-bg p-3 text-sm text-custom-shadow/80 dark:bg-custom-bg-dark">{{ props.vehicle.verification_remark }}</p>
            </div>
        </div>
    </PreviewCard>
</template>
