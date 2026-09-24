<script setup lang="ts">
import { PreviewCard } from '@/components/ui/_preview-card';
import { Badge } from '@/components/ui/badge';
import { RiBus2Line } from 'vue-remix-icons';

import type { DispatchItem } from './types';

defineProps<{
    dispatch: DispatchItem;
    statusClass: string;
    statusDot: string;
    statusLabel: string;
}>();

defineEmits<{ close: [] }>();
</script>

<template>
    <PreviewCard :title="dispatch.plate_number" description="Preview" title-class="uppercase" @close="$emit('close')">
        <div class="flex aspect-4/3 items-center justify-center rounded-md border border-dashed border-custom-bg-dark bg-custom-bg text-custom-shadow/70 dark:border-custom-bg-light dark:bg-custom-bg-dark">
            <RiBus2Line class="h-16 w-16 shrink-0" />
        </div>

        <div class="space-y-3 pt-2">
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Status</span>
                <Badge :class="['gap-1.5', statusClass]">
                    <span :class="['h-1.5 w-1.5 rounded-full', statusDot]" />
                    {{ statusLabel }}
                </Badge>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Vehicle</span>
                <div class="min-w-0 text-right text-sm text-custom-shadow/80">
                    <p>{{ dispatch.vehicle?.make_model ?? dispatch.vehicle?.vehicle_type ?? '—' }}</p>
                    <p v-if="dispatch.vehicle?.body_number" class="text-xs text-custom-shadow/70">Body #{{ dispatch.vehicle.body_number }}</p>
                </div>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Driver</span>
                <span class="text-right text-sm text-custom-shadow/80">{{ dispatch.driver?.name ?? 'Unassigned' }}</span>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Gate / Bay</span>
                <span class="text-right text-sm text-custom-shadow/80">{{ dispatch.gate?.gate_name ?? '—' }} / Bay {{ dispatch.bay_number }}</span>
            </div>
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Passengers</span>
                <span class="text-sm text-custom-shadow/80">{{ dispatch.pax_count }}</span>
            </div>
            <div class="space-y-2">
                <p class="text-sm font-semibold text-custom-shadow">Dispatch Timeline</p>
                <div class="flex items-center justify-between gap-3 rounded-md bg-custom-bg px-3 py-2 dark:bg-custom-bg-dark">
                    <span class="text-sm font-semibold">Arrived</span>
                    <span class="shrink-0 text-xs text-custom-shadow/70">{{ dispatch.arrived_at_formatted ?? '—' }}</span>
                </div>
                <div class="flex items-center justify-between gap-3 rounded-md bg-custom-bg px-3 py-2 dark:bg-custom-bg-dark">
                    <span class="text-sm font-semibold">Departed</span>
                    <span class="shrink-0 text-xs text-custom-shadow/70">{{ dispatch.departed_at_formatted ?? '—' }}</span>
                </div>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Dispatcher</span>
                <span class="text-right text-sm text-custom-shadow/80">{{ dispatch.dispatcher?.name ?? '—' }}</span>
            </div>
            <div v-if="dispatch.remarks" class="space-y-1">
                <span class="text-sm font-semibold text-custom-shadow">Remarks</span>
                <p class="rounded-md bg-custom-bg p-3 text-sm text-custom-shadow/80 dark:bg-custom-bg-dark">{{ dispatch.remarks }}</p>
            </div>
        </div>
    </PreviewCard>
</template>
