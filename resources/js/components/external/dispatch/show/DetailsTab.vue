<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import RouteDetailsDialog from '@/components/internal/company/vehicles/RouteDetailsDialog.vue';
import { CardSeparator } from '@/components/ui/_card-separator';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { can } from '@/lib/can';
import { humanize } from '@/lib/format';
import { operationalStatusClass, operationalStatusDot } from '@/lib/vehicle-status';
import { edit as editVehicle } from '@/actions/App/Http/Controllers/CompanyVehicleController';
import { edit as editEmployee } from '@/routes/employee-users';
import {
    RiBusLine,
    RiCarLine,
    RiDoorOpenLine,
    RiEyeLine,
    RiFingerprintLine,
    RiGroupLine,
    RiHashtag,
    RiParkingBoxLine,
    RiRouteLine,
    RiSettings3Line,
    RiUserLine,
} from 'vue-remix-icons';

import type { DispatchMapConfig, DispatchModel, DispatchRoute } from './types';

const props = defineProps<{
    dispatch: DispatchModel;
    routes: DispatchRoute[];
    mapConfig: DispatchMapConfig;
}>();

const canViewVehicle = can('external_vehicles.view');
const canViewEmployee = can('external_users.view');

const usernamePillClass =
    'ml-1 rounded-md bg-custom-bg px-2 font-mono font-normal tracking-widest dark:bg-custom-bg-light';
const linkedPillClass = 'transition-colors duration-200 hover:bg-custom-secondary/20 hover:text-custom-shadow';

const vehicleUrl = computed(() =>
    canViewVehicle && props.dispatch.vehicle ? editVehicle(props.dispatch.vehicle.id).url : null,
);

const routeDialogOpen = ref(false);

const vehicleRoute = computed(
    () => props.routes.find((route) => route.id === props.dispatch.vehicle?.route?.id) ?? null,
);

const dispatchFields = computed(() => [
    { label: 'Gate', icon: RiDoorOpenLine, value: props.dispatch.gate?.gate_name },
    { label: 'Bay', icon: RiParkingBoxLine, value: props.dispatch.bay_number },
    { label: 'Passengers', icon: RiGroupLine, value: props.dispatch.pax_count },
]);

const vehicleFields = computed(() => [
    { label: 'Body Number', icon: RiHashtag, value: props.dispatch.vehicle?.body_number },
    { label: 'Vehicle Type', icon: RiBusLine, value: props.dispatch.vehicle?.vehicle_type },
]);

const personnelFields = computed(() => [
    { label: 'Driver', icon: RiUserLine, person: props.dispatch.driver, empty: 'Unassigned' },
    { label: 'Dispatcher', icon: RiFingerprintLine, person: props.dispatch.dispatcher, empty: '—' },
]);

function personUrl(field: { person?: { id: number } | null }): string | null {
    return canViewEmployee && field.person ? editEmployee(field.person.id).url : null;
}

function display(value: unknown): string {
    return value === null || value === undefined || value === '' ? '—' : String(value);
}
</script>

<template>
    <div>
    <Card>
        <CardHeader>
            <CardTitle>Dispatch Details</CardTitle>
            <CardDescription>Recorded details of this dispatch. Dispatch records are read-only.</CardDescription>
        </CardHeader>
        <CardContent class="flex flex-col gap-4 lg:flex-row">
            <div class="min-w-0 flex-1">
                <CardSeparator title="Dispatch Info" />
                <div class="my-2 flex flex-col gap-1 text-sm text-custom-shadow">
                    <div v-for="field in dispatchFields" :key="field.label" class="flex flex-row items-center justify-between gap-2">
                        <span class="inline-flex shrink-0 items-center gap-2">
                            <component :is="field.icon" class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                            {{ field.label }}
                        </span>
                        <span class="truncate text-custom-shadow/80">{{ display(field.value) }}</span>
                    </div>
                    <div class="group flex flex-row items-center justify-between gap-2">
                        <span class="inline-flex shrink-0 items-center gap-2">
                            <RiRouteLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                            Route
                        </span>
                        <button
                            v-if="vehicleRoute"
                            type="button"
                            class="flex min-w-0 cursor-pointer items-center text-custom-shadow/80 transition-colors duration-200 hover:text-custom-shadow"
                            title="View route"
                            aria-label="View route"
                            @click="routeDialogOpen = true"
                        >
                            <span class="truncate">{{ vehicleRoute.route_name }}</span>
                            <RiEyeLine class="ml-0 h-4 w-0 shrink-0 opacity-0 transition-all duration-300 ease-out group-hover:ml-2 group-hover:w-4 group-hover:opacity-100 group-focus-visible:ml-2 group-focus-visible:w-4 group-focus-visible:opacity-100" />
                        </button>
                        <span v-else class="truncate text-custom-shadow/80">{{ display(dispatch.vehicle?.route?.route_name) }}</span>
                    </div>
                </div>

                <CardSeparator title="Remarks" />
                <p
                    class="my-2 rounded-md bg-custom-bg p-3 text-sm dark:bg-custom-bg-dark"
                    :class="dispatch.remarks ? 'text-custom-shadow/80' : 'text-custom-shadow/60 italic'"
                >
                    {{ dispatch.remarks || 'No remarks recorded for this dispatch.' }}
                </p>
            </div>

            <Separator orientation="vertical" class="hidden lg:block" />

            <div class="min-w-0 flex-1">
                <CardSeparator title="Vehicle" />
                <div class="my-2 flex flex-col gap-1 text-sm text-custom-shadow">
                    <div class="flex flex-row items-center justify-between gap-2">
                        <span class="inline-flex shrink-0 items-center gap-2">
                            <RiCarLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                            Plate Number
                        </span>
                        <component
                            :is="vehicleUrl ? Link : 'span'"
                            :href="vehicleUrl ?? undefined"
                            :title="vehicleUrl ? 'View vehicle' : undefined"
                            class="truncate rounded-md bg-custom-bg px-2 font-mono tracking-widest uppercase dark:bg-custom-bg-light"
                            :class="vehicleUrl ? linkedPillClass : ''"
                        >
                            {{ dispatch.plate_number }}
                        </component>
                    </div>
                    <div v-for="field in vehicleFields" :key="field.label" class="flex flex-row items-center justify-between gap-2">
                        <span class="inline-flex shrink-0 items-center gap-2">
                            <component :is="field.icon" class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                            {{ field.label }}
                        </span>
                        <span class="truncate text-custom-shadow/80">{{ display(field.value) }}</span>
                    </div>
                    <div class="flex flex-row items-center justify-between gap-2">
                        <span class="inline-flex shrink-0 items-center gap-2">
                            <RiBusLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                            Vehicle Status
                        </span>
                        <Badge :class="['gap-1.5', operationalStatusClass(dispatch.vehicle?.status ?? '')]">
                            <span :class="['h-1.5 w-1.5 rounded-full', operationalStatusDot(dispatch.vehicle?.status ?? '')]" />
                            {{ humanize(dispatch.vehicle?.status ?? null) }}
                        </Badge>
                    </div>
                </div>

                <CardSeparator title="Personnel" />
                <div class="my-2 flex flex-col gap-1 text-sm text-custom-shadow">
                    <div v-for="field in personnelFields" :key="field.label" class="flex flex-row items-center justify-between gap-2">
                        <span class="inline-flex shrink-0 items-center gap-2">
                            <component :is="field.icon" class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                            {{ field.label }}
                        </span>
                        <span class="min-w-0 truncate text-right" :class="field.person ? 'text-custom-shadow/80' : 'text-custom-shadow/60 italic'">
                            {{ field.person?.name ?? field.empty }}
                            <component
                                :is="personUrl(field) ? Link : 'span'"
                                v-if="field.person?.username"
                                :href="personUrl(field) ?? undefined"
                                :title="personUrl(field) ? 'View user' : undefined"
                                :class="[usernamePillClass, personUrl(field) ? linkedPillClass : '']"
                            >{{ field.person.username }}</component>
                        </span>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>

    <RouteDetailsDialog v-model:open="routeDialogOpen" :route="vehicleRoute" :map-config="mapConfig" />
    </div>
</template>
