<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { PreviewCard } from '@/components/ui/_preview-card';
import { RiClipboardLine } from 'vue-remix-icons';

type DispatchRoute = {
    route_name: string | null;
    origin_name: string | null;
    destination_name: string | null;
};

type DispatchVehicle = {
    plate_number: string | null;
    vehicle_type: string | null;
    make_model: string | null;
    route: DispatchRoute | null;
};

type Dispatch = {
    id: number;
    status: 'pending' | 'arrived' | 'departed' | string;
    bay_number: string | null;
    pax_count: number | null;
    dispatched_at: string | null;
    arrived_at: string | null;
    departed_at: string | null;
    company: { id: number; company_name: string } | null;
    vehicle: DispatchVehicle | null;
    gate: { gate_name: string } | null;
    dispatcher: { name: string } | null;
    driver: { name: string } | null;
};

const props = defineProps<{
    dispatch: Dispatch;
}>();

defineEmits<{ close: [] }>();

function prettyStatus(value: string | null | undefined) {
    return String(value ?? 'unknown')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function statusClass(status: string | null | undefined): string {
    switch (status) {
        case 'arrived':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'pending':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        default:
            return 'bg-slate-100 text-slate-500 border-0';
    }
}

function statusDot(status: string | null | undefined): string {
    switch (status) {
        case 'arrived':
            return 'bg-emerald-500';
        case 'pending':
            return 'bg-amber-400';
        default:
            return 'bg-slate-400';
    }
}

function routeLabel(vehicle: DispatchVehicle | null): string {
    const route = vehicle?.route;
    if (!route) return '—';
    if (route.route_name) return route.route_name;
    if (route.origin_name || route.destination_name) {
        return `${route.origin_name ?? '—'} to ${route.destination_name ?? '—'}`;
    }
    return '—';
}
</script>

<template>
    <PreviewCard
        :title="props.dispatch.vehicle?.plate_number || `Dispatch #${props.dispatch.id}`"
        title-class="uppercase"
        @close="$emit('close')"
    >
        <div class="flex aspect-4/3 items-center justify-center overflow-hidden rounded-md border border-dashed border-custom-bg-dark bg-custom-bg text-custom-shadow/70 dark:border-none dark:bg-custom-bg-dark">
            <RiClipboardLine class="h-16 w-16" />
        </div>

        <div class="space-y-2 pt-2">
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Status</span>
                <Badge :class="['gap-1.5', statusClass(dispatch.status)]">
                    <span :class="['h-1.5 w-1.5 rounded-full', statusDot(dispatch.status)]" />
                    {{ prettyStatus(dispatch.status) }}
                </Badge>
            </div>

            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Company</span>
                <span class="text-right text-sm">{{ dispatch.company?.company_name || '—' }}</span>
            </div>

            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Vehicle</span>
                <span class="text-right text-sm">{{ dispatch.vehicle?.vehicle_type || dispatch.vehicle?.make_model || 'Not recorded' }}</span>
            </div>

            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Route</span>
                <span class="text-right text-sm">{{ routeLabel(dispatch.vehicle) }}</span>
            </div>

            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Gate</span>
                <span class="text-right text-sm">{{ dispatch.gate?.gate_name || 'Not assigned' }}</span>
            </div>

            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Bay</span>
                <span class="text-right text-sm">{{ dispatch.bay_number || 'Not assigned' }}</span>
            </div>

            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">PAX Count</span>
                <span class="text-right text-sm">{{ dispatch.pax_count ?? 'Not recorded' }}</span>
            </div>

            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Dispatcher</span>
                <span class="text-right text-sm">{{ dispatch.dispatcher?.name || 'Not recorded' }}</span>
            </div>

            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Driver</span>
                <span class="text-right text-sm">{{ dispatch.driver?.name || 'Not recorded' }}</span>
            </div>

            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Dispatched At</span>
                <span class="text-right text-sm">{{ dispatch.dispatched_at || 'Not recorded' }}</span>
            </div>

            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Arrived At</span>
                <span class="text-right text-sm">{{ dispatch.arrived_at || 'Not recorded' }}</span>
            </div>

            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Departed At</span>
                <span class="text-right text-sm">{{ dispatch.departed_at || 'Not recorded' }}</span>
            </div>
        </div>
    </PreviewCard>
</template>
