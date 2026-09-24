<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { PreviewCard } from '@/components/ui/_preview-card';
import { RiBus2Line } from 'vue-remix-icons';
import {
    operationalStatusClass,
    operationalStatusDot,
    operationalStatusLabel,
} from '@/lib/vehicle-status';

type VehicleDocument = {
    id: number;
    document_type: string;
    status: string;
    expires_at?: string | null;
};

type VehicleItem = {
    id: number;
    plate_number: string;
    body_number?: string | null;
    capacity?: number | null;
    make_model?: string | null;
    status: string;
    operator_remark?: string | null;
    suspension_remark?: string | null;
    route?: { route_name: string } | null;
    vehicle_type?: { type_name: string } | null;
    documents?: VehicleDocument[];
};

const props = defineProps<{
    vehicle: VehicleItem;
}>();

defineEmits<{ close: [] }>();

function documentsCount(documents?: VehicleDocument[]) {
    return documents?.length ?? 0;
}
</script>

<template>
    <PreviewCard :title="props.vehicle.plate_number || 'Vehicle'" description="Preview" title-class="uppercase" @close="$emit('close')">
        <div class="flex aspect-4/3 items-center justify-center rounded-md border border-dashed border-custom-bg-dark bg-custom-bg text-custom-shadow/70 dark:border-custom-bg-light dark:bg-custom-bg-dark">
            <RiBus2Line class="h-16 w-16 shrink-0" />
        </div>

        <div class="space-y-3 pt-2">
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Status</span>
                <Badge :class="['gap-1.5', operationalStatusClass(vehicle.status)]">
                    <span :class="['h-1.5 w-1.5 rounded-full', operationalStatusDot(vehicle.status)]" />
                    {{ operationalStatusLabel(vehicle.status) }}
                </Badge>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Route</span>
                <span class="min-w-0 truncate text-right text-sm text-custom-shadow/80">{{ vehicle.route?.route_name || 'Not assigned' }}</span>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Vehicle Type</span>
                <span class="text-right text-sm text-custom-shadow/80">{{ vehicle.vehicle_type?.type_name ?? '—' }}</span>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Make / Model</span>
                <span class="text-right text-sm text-custom-shadow/80">{{ vehicle.make_model || 'Not recorded' }}</span>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Body Number</span>
                <span class="text-right text-sm text-custom-shadow/80">{{ vehicle.body_number || 'Not recorded' }}</span>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Capacity</span>
                <span class="text-right text-sm text-custom-shadow/80">{{ vehicle.capacity ?? 'Not recorded' }}</span>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Documents</span>
                <span class="text-right text-sm text-custom-shadow/80">{{ documentsCount(vehicle.documents) }}</span>
            </div>
            <div v-if="vehicle.operator_remark" class="space-y-1">
                <span class="text-sm font-semibold text-custom-shadow">Operator Remark</span>
                <p class="rounded-md bg-custom-bg p-3 text-sm text-custom-shadow/80 dark:bg-custom-bg-dark">{{ vehicle.operator_remark }}</p>
            </div>
            <div v-if="vehicle.suspension_remark" class="space-y-1">
                <span class="text-sm font-semibold text-custom-shadow">Suspension Remark</span>
                <p class="rounded-md bg-custom-bg p-3 text-sm text-custom-shadow/80 dark:bg-custom-bg-dark">{{ vehicle.suspension_remark }}</p>
            </div>
        </div>
    </PreviewCard>
</template>
