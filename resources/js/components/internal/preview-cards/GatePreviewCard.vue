<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { PreviewCard, PreviewCardRow } from '@/components/ui/_preview-card';
import { RiImageAddLine } from 'vue-remix-icons';

type Gate = {
    id: number;
    gate_name: string;
    status?: 'active' | 'inactive' | null;
    picture_url?: string | null;
    location?: { label?: string | null } | null;
    bays?: number | null;
    bay_statuses?: { status: string }[];
    assigned_routes?: { id: number; route_name: string; status: string }[];
    deleted_at_human?: string | null;
    deleter?: { name?: string | null } | null;
};

const props = defineProps<{
    gate: Gate;
    archived?: boolean;
}>();

defineEmits<{ close: [] }>();

function statusClass(status: Gate['status']): string {
    return status === 'active'
        ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
        : 'bg-slate-100 text-slate-500 border-0';
}

function statusDot(status: Gate['status']): string {
    return status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400';
}
</script>

<template>
    <PreviewCard
        :title="props.gate.gate_name"
        :description="props.archived ? 'Archived preview' : 'Preview'"
        title-class="capitalize"
        @close="$emit('close')"
    >
        <div class="flex aspect-4/3 items-center justify-center overflow-hidden rounded-md border border-dashed border-custom-bg-dark dark:border-none bg-custom-bg dark:bg-custom-bg-dark text-custom-shadow/70">
            <img
                v-if="gate.picture_url"
                :src="gate.picture_url"
                :alt="`${gate.gate_name} photo`"
                class="h-full w-full object-cover"
            />
            <div v-else class="flex flex-col items-center gap-1 text-center">
                <RiImageAddLine class="h-6 w-6" />
            </div>
        </div>

        <div class="space-y-2 pt-2">
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm text-custom-shadow font-semibold">Status</span>
                <Badge :class="['gap-1.5', statusClass(gate.status)]">
                    <span :class="['h-1.5 w-1.5 rounded-full', statusDot(gate.status)]" />
                    {{ gate.status === 'active' ? 'Active' : 'Inactive' }}
                </Badge>
            </div>

            <div v-if="gate.location" class="flex items-start justify-between gap-3">
                <span class="text-sm text-custom-shadow font-semibold">Location</span>
                <span class="text-right text-sm">
                    {{ gate.location?.label }}
                </span>
            </div>

            <!-- CODE: <div class="flex items-center justify-between gap-3 border-b border-custom-bg-dark pb-3">
                <span class="text-sm text-custom-shadow/70">Created By</span>
                <span class="truncate text-sm font-medium">{{ gate.creator?.name ?? 'Not recorded' }}</span>
            </div> -->

            <div v-if="gate.bay_statuses" class="space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-custom-shadow">Bay Status</p>
                    <span class="text-sm text-custom-shadow">
                        {{ (gate.bay_statuses ?? []).filter((bay) => bay.status === 'occupied').length }} occupied out of {{ gate.bays }}
                    </span>
                </div>

                <!-- CODE: <div
                    v-if="(gate.bay_statuses ?? []).length > 0"
                    class="space-y-2"
                >
                    <div
                        v-for="bay in gate.bay_statuses"
                        :key="bay.bay_number"
                        class="rounded-md bg-custom-bg px-3 py-2"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-medium">Bay {{ bay.bay_number }}</span>
                            <Badge
                                :class="bay.status === 'occupied'
                                    ? 'bg-custom-secondary/20 text-custom-shadow'
                                    : 'bg-emerald-100 text-emerald-700'"
                            >
                                {{ bay.status === 'occupied' ? 'Occupied' : 'Empty' }}
                            </Badge>
                        </div>
                        <p
                            v-if="bay.status === 'occupied'"
                            class="mt-1 text-xs text-custom-shadow/70"
                        >
                            {{ bay.vehicle?.plate_number ?? 'Unknown unit' }}
                            <span v-if="bay.vehicle?.body_number">/ Body #{{ bay.vehicle.body_number }}</span>
                            - {{ bay.company?.company_name ?? 'Unknown company' }}
                        </p>
                    </div>
                </div>
                <p
                    v-else
                    class="rounded-md bg-custom-bg px-3 py-2 text-sm text-custom-shadow/70"
                >
                    No bays configured.
                </p> -->
            </div>

            <div v-if="gate.assigned_routes" class="space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-custom-shadow">Assigned Routes</p>
                    <span class="text-sm text-custom-shadow">
                        {{ (gate.assigned_routes ?? []).length }}
                    </span>
                </div>
                <!-- TODO: redesign the routes list, i dont like it, and the image part too -->

                <div
                    v-if="(gate.assigned_routes ?? []).length > 0"
                    class="space-y-2"
                >
                    <div
                        v-for="route in (gate.assigned_routes ?? [])"
                        :key="route.id"
                        class="flex items-center justify-between gap-3 rounded-md bg-custom-bg dark:bg-custom-bg-dark px-3 py-2"
                    >
                        <span class="truncate text-sm font-medium">{{ route.route_name }}</span>
                        <span class="shrink-0 text-xs capitalize text-custom-shadow/70">{{ route.status }}</span>
                    </div>
                </div>
                <p
                    v-else
                    class="rounded-md bg-custom-bg dark:bg-custom-bg-dark px-3 py-2 text-sm text-custom-shadow/70"
                >
                    No routes assigned.
                </p>
            </div>
        </div>

        <div v-if="props.archived" class="space-y-3 pt-1">
            <PreviewCardRow v-if="props.gate.bays" label="Bays">{{ props.gate.bays }}</PreviewCardRow>
            <PreviewCardRow label="Archived">{{ props.gate.deleted_at_human || '—' }}</PreviewCardRow>
            <PreviewCardRow v-if="props.gate.deleter !== undefined" label="Archived By">{{ props.gate.deleter?.name || '—' }}</PreviewCardRow>
        </div>
    </PreviewCard>
</template>
