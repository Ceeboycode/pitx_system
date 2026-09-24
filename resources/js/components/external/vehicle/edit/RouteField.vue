<script setup lang="ts">
import { computed, ref } from 'vue';
import type { AcceptableValue } from 'reka-ui';

import RouteDetailsDialog from '@/components/internal/company/vehicles/RouteDetailsDialog.vue';
import SearchInput from '@/components/SearchInput.vue';
import EditableField from '@/components/ui/_field/EditableField.vue';
import { InputMessage } from '@/components/ui/_input-message';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RiEyeLine, RiRouteLine } from 'vue-remix-icons';

import type { MapConfig, RouteItem } from './types';

/**
 * The "Route" row of the vehicle details: it reads like the other gated rows (label on the left, the
 * value on the right, a select while editing) and a button beside the value opens the route's map and stops.
 */
const props = defineProps<{
    routes: RouteItem[];
    mapConfig: MapConfig;
    /** Without it the row shows the route name as plain text. */
    canEdit: boolean;
    error?: string;
}>();

/** The id of the selected route (`route_id` of the vehicle form). */
const routeId = defineModel<string>({ required: true });

const search = ref('');
const dialogOpen = ref(false);

const selectedRoute = computed(() => props.routes.find((route) => String(route.id) === String(routeId.value)) ?? null);

const filteredRoutes = computed(() => {
    const keyword = search.value.trim().toLowerCase();

    if (!keyword) return props.routes;

    return props.routes.filter((route) =>
        [route.route_name, route.origin_name ?? '', route.destination_name ?? '', route.gate?.gate_name ?? '']
            .join(' ')
            .toLowerCase()
            .includes(keyword),
    );
});

function onSelect(value: AcceptableValue) {
    routeId.value = value === null || value === undefined ? '' : String(value);
}
</script>

<template>
    <div>
    <div class="group flex flex-row items-center justify-between gap-2 overflow-hidden">
        <div class="inline-flex shrink-0 items-center gap-2">
            <RiRouteLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
            <Label for="vehicle_route_id">Route</Label>
        </div>

        <div class="flex flex-row items-center justify-end gap-2">
            <EditableField :editable="canEdit">
                <template #edit>
                    <span class="flex min-w-0 flex-1 flex-row items-center">
                        <Select :model-value="routeId" @update:model-value="onSelect">
                            <SelectTrigger id="vehicle_route_id" variant="inline-edit">
                                <SelectValue placeholder="Select a route" />
                            </SelectTrigger>
                            <SelectContent class="w-fit">
                                <SelectGroup>
                                    <SearchInput v-model="search" placeholder="Search route..." class="mb-2" @keydown.stop />

                                    <SelectItem
                                        v-for="route in filteredRoutes"
                                        :key="route.id"
                                        :value="String(route.id)"
                                        :title="`${route.origin_name || '—'} → ${route.destination_name || '—'}${route.gate?.gate_name ? ' • ' + route.gate.gate_name : ''}`"
                                    >
                                        {{ route.route_name }}
                                    </SelectItem>

                                    <p v-if="filteredRoutes.length === 0" class="px-2 py-1 text-sm text-custom-shadow/80">
                                        No route found.
                                    </p>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </span>
                </template>
                <span class="min-w-0 flex-1 truncate text-right text-sm font-semibold">{{ selectedRoute?.route_name ?? '—' }}</span>
            </EditableField>

            <button
                v-if="selectedRoute"
                type="button"
                class="shrink-0 hidden group-hover:flex cursor-pointer text-custom-shadow/80 transition-all duration-200 hover:text-custom-shadow"
                title="View route"
                aria-label="View route"
                @click="dialogOpen = true"
            >
                <RiEyeLine class="h-4 w-4 shrink-0" />
            </button>
        </div>

        <InputMessage v-if="canEdit" variant="destructive" :message="error" />
    </div>

    <RouteDetailsDialog v-model:open="dialogOpen" :route="selectedRoute" :map-config="mapConfig" />
    </div>
</template>
