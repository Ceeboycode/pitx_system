<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

import DispatchController from '@/actions/App/Http/Controllers/DispatchController';
import { AppDialog } from '@/components/ui/_app-dialog';
import { InputMessage } from '@/components/ui/_input-message';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { RiLoaderLine } from 'vue-remix-icons';

import type {
    DispatchDriverOption,
    DispatchGateOption,
    DispatchItem,
    DispatchVehicleOption,
} from './types';

const open = defineModel<boolean>('open');

const props = defineProps<{
    /** The dispatch being edited; null opens the dialog in add mode. */
    dispatch: DispatchItem | null;
    vehicles: DispatchVehicleOption[];
    drivers: DispatchDriverOption[];
    gates: DispatchGateOption[];
    assignedDriverIdsToday: number[];
    assignedVehicleIdsActive: number[];
}>();

const form = useForm({
    vehicle_id: '',
    driver_user_id: 'unassigned',
    gate_id: '',
    bay_number: '',
    remarks: '',
});

const isEditing = computed(() => props.dispatch !== null);

const selectedVehicle = computed(
    () => props.vehicles.find((v) => String(v.id) === form.vehicle_id) ?? null,
);
const selectedGate = computed(
    () => props.gates.find((g) => String(g.id) === form.gate_id) ?? null,
);
const bayOptions = computed(() => selectedGate.value?.bay_options ?? []);
const isGateAutoLocked = computed(() => !!selectedVehicle.value?.route?.gate_id);
const selectedVehicleRouteGateInactive = computed(
    () => selectedVehicle.value?.route?.gate?.status === 'inactive',
);
const selectedVehicleRouteGateName = computed(
    () => selectedVehicle.value?.route?.gate?.gate_name ?? 'This route gate',
);

function isVehicleUnavailable(vehicleId: number): boolean {
    if (props.dispatch?.vehicle?.id === vehicleId) {
        return false;
    }

    return props.assignedVehicleIdsActive.includes(vehicleId);
}

function isDriverUnavailable(driverId: number): boolean {
    if (props.dispatch?.driver?.id === driverId) {
        return false;
    }

    return props.assignedDriverIdsToday.includes(driverId);
}

function onVehicleChange(value: unknown) {
    form.vehicle_id = String(value ?? '');

    const vehicle = selectedVehicle.value;

    if (vehicle?.route?.gate?.status === 'inactive') {
        form.gate_id = '';
        form.bay_number = '';
        return;
    }

    const routeGateId = vehicle?.route?.gate_id ? String(vehicle.route.gate_id) : '';

    if (routeGateId && form.gate_id !== routeGateId) {
        form.gate_id = routeGateId;
        form.bay_number = '';
    }
}

function onGateChange(value: unknown) {
    form.gate_id = String(value ?? '');
    form.bay_number = '';
}

function resetForm() {
    form.transform((data) => data);
    form.reset();
    form.clearErrors();
}

function fillFromDispatch(dispatch: DispatchItem) {
    onVehicleChange(dispatch.vehicle?.id ? String(dispatch.vehicle.id) : '');
    form.driver_user_id = dispatch.driver?.id ? String(dispatch.driver.id) : 'unassigned';
    if (!isGateAutoLocked.value) {
        form.gate_id = dispatch.gate?.id ? String(dispatch.gate.id) : '';
    }
    form.bay_number = String(dispatch.bay_number ?? '');
    form.remarks = dispatch.remarks ?? '';
}

watch(open, (isOpen) => {
    resetForm();
    if (isOpen && props.dispatch) {
        fillFromDispatch(props.dispatch);
    }
});

function submit() {
    form.transform((data) => ({
        ...data,
        driver_user_id: data.driver_user_id !== 'unassigned' ? data.driver_user_id : null,
    }));

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    };

    if (props.dispatch) {
        form.put(DispatchController.update(props.dispatch.id).url, options);
        return;
    }

    form.post(DispatchController.store().url, options);
}
</script>

<template>
    <AppDialog
        v-model:open="open"
        :title="isEditing ? 'Edit Dispatch' : 'Add New Dispatch'"
        :description="isEditing ? undefined : 'Arrival time is recorded automatically once the dispatch is added.'"
        size="lg"
        form
        @submit="submit"
    >
        <div class="flex flex-col gap-y-2">
            <div class="space-y-1">
                <Label for="dispatch_vehicle_id">Vehicle</Label>
                <Select :model-value="form.vehicle_id" @update:model-value="onVehicleChange">
                    <SelectTrigger id="dispatch_vehicle_id" class="w-full">
                        <SelectValue placeholder="Select a vehicle" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="vehicle in vehicles"
                            :key="vehicle.id"
                            :value="String(vehicle.id)"
                            :disabled="isVehicleUnavailable(vehicle.id)"
                        >
                            {{ vehicle.label }}{{ isVehicleUnavailable(vehicle.id) ? ' (Already arrived)' : '' }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputMessage variant="destructive" :message="form.errors.vehicle_id" />
            </div>

            <div class="space-y-1">
                <Label for="dispatch_driver_user_id">Driver</Label>
                <Select v-model="form.driver_user_id">
                    <SelectTrigger id="dispatch_driver_user_id" class="w-full">
                        <SelectValue placeholder="Assign a driver" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="unassigned">No driver assigned</SelectItem>
                        <SelectItem
                            v-for="driver in drivers"
                            :key="driver.id"
                            :value="String(driver.id)"
                            :disabled="isDriverUnavailable(driver.id)"
                        >
                            {{ driver.label }}{{ isDriverUnavailable(driver.id) ? ' (Already assigned today)' : '' }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputMessage variant="destructive" :message="form.errors.driver_user_id" />
            </div>

            <div class="space-y-1">
                <Label for="dispatch_gate_id">Gate</Label>
                <Select :disabled="isGateAutoLocked" :model-value="form.gate_id" @update:model-value="onGateChange">
                    <SelectTrigger id="dispatch_gate_id" class="w-full">
                        <SelectValue placeholder="Select a gate" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="gate in gates" :key="gate.id" :value="String(gate.id)">
                            {{ gate.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputMessage v-if="isGateAutoLocked" variant="info" message="Gate is set automatically from the vehicle's route." />
                <InputMessage
                    v-if="selectedVehicleRouteGateInactive"
                    variant="destructive"
                    :message="`${selectedVehicleRouteGateName} is inactive. Contact the terminal manager to activate it before dispatching this vehicle.`"
                />
                <InputMessage variant="destructive" :message="form.errors.gate_id" />
            </div>

            <div class="space-y-1">
                <Label for="dispatch_bay_number">Bay Number</Label>
                <Select v-model="form.bay_number" :disabled="!selectedGate">
                    <SelectTrigger id="dispatch_bay_number" class="w-full">
                        <SelectValue placeholder="Select a bay" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="bay in bayOptions" :key="bay.value" :value="String(bay.value)">
                            {{ bay.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputMessage variant="destructive" :message="form.errors.bay_number" />
            </div>

            <div class="space-y-1">
                <Label for="dispatch_remarks">
                    Remarks
                    <span class="text-xs text-custom-shadow/80">(optional)</span>
                </Label>
                <Input id="dispatch_remarks" v-model="form.remarks" placeholder="e.g. Late arrival due to traffic" />
                <InputMessage variant="destructive" :message="form.errors.remarks" />
            </div>
        </div>

        <template #footer>
            <Button variant="float" type="button" @click="open = false">Cancel</Button>
            <Button
                type="submit"
                variant="float-primary"
                :disabled="form.processing || selectedVehicleRouteGateInactive"
            >
                <RiLoaderLine v-if="form.processing" class="h-4 w-4 shrink-0 animate-spin" />
                <template v-if="isEditing">{{ form.processing ? 'Saving...' : 'Save Changes' }}</template>
                <template v-else>{{ form.processing ? 'Adding...' : 'Add Dispatch' }}</template>
            </Button>
        </template>
    </AppDialog>
</template>
