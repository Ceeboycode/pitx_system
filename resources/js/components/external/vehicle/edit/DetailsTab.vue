<script setup lang="ts">
import { computed } from 'vue';
import type { AcceptableValue } from 'reka-ui';

import { CardSeparator } from '@/components/ui/_card-separator';
import EditableField from '@/components/ui/_field/EditableField.vue';
import { InputMessage } from '@/components/ui/_input-message';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import {
    RiBusLine,
    RiCalendarLine,
    RiEditLine,
    RiFileCheckLine,
    RiGroupLine,
    RiHashtag,
    RiPaletteLine,
    RiShieldLine,
} from 'vue-remix-icons';

import { formatDate, formatDateTime } from '@/lib/format';
import {
    operationalStatusClass,
    operationalStatusDot,
    operationalStatusLabel,
    verificationStatusClass,
    verificationStatusDot,
    verificationStatusLabel,
} from '@/lib/vehicle-status';

import RouteField from './RouteField.vue';
import type { MapConfig, RouteItem, VehicleForm, VehicleModel } from './types';

const props = defineProps<{
    vehicle: VehicleModel;
    vehicleTypes: Array<{ id: number; type_name: string }>;
    routes: RouteItem[];
    mapConfig: MapConfig;
    /** Drives every EditableField below: without it the tab shows static text and no Save/Cancel. */
    canEdit: boolean;
}>();

const form = defineModel<VehicleForm>('form', { required: true });

const emit = defineEmits<{
    submit: [];
    reset: [];
}>();

type TextFieldKey = 'plate_number' | 'body_number' | 'capacity' | 'color' | 'make_model' | 'engine_number' | 'chassis_number';

const toUppercase = (value: string) => value.toUpperCase();
const toTitleCase = (value: string) => value.toLowerCase().replace(/\b\w/g, (char) => char.toUpperCase());
const toNormalCase = (value: string) => {
    const text = value.trimStart();

    return text ? text.charAt(0).toUpperCase() + text.slice(1).toLowerCase() : '';
};

const vehicleFields: { key: TextFieldKey; label: string; icon: typeof RiHashtag; placeholder: string; type?: string; format: (value: string) => string }[] = [
    { key: 'plate_number', label: 'Plate No.', icon: RiHashtag, placeholder: 'e.g. ABC1234', format: toUppercase },
    { key: 'body_number', label: 'Body No.', icon: RiHashtag, placeholder: 'e.g. B-1024', format: toUppercase },
    { key: 'capacity', label: 'Capacity', icon: RiGroupLine, placeholder: 'e.g. 40', type: 'number', format: (value) => value },
    { key: 'color', label: 'Color', icon: RiPaletteLine, placeholder: 'e.g. White', format: toNormalCase },
    { key: 'make_model', label: 'Make / Model', icon: RiBusLine, placeholder: 'e.g. Hyundai County', format: toTitleCase },
];

const identificationFields: typeof vehicleFields = [
    { key: 'engine_number', label: 'Engine No.', icon: RiShieldLine, placeholder: 'e.g. ENG-123456', format: toUppercase },
    { key: 'chassis_number', label: 'Chassis No.', icon: RiShieldLine, placeholder: 'e.g. CHS-123456', format: toUppercase },
];

function setField(key: TextFieldKey, value: string | number, format: (value: string) => string) {
    const formatted = format(String(value ?? ''));

    if (key === 'capacity') form.value.capacity = formatted;
    else form.value[key] = formatted;
}

function savedValue(key: TextFieldKey) {
    const value = props.vehicle[key];

    return value === null || value === undefined || value === '' ? '—' : value;
}

function onVehicleTypeChange(value: AcceptableValue) {
    form.value.vehicle_type_id = value === null || value === undefined ? null : Number(value);
}

const vehicleTypeName = computed(() => props.vehicle.vehicle_type ?? '—');

const editIconClass =
    'h-4 w-0 shrink-0 overflow-hidden text-custom-shadow/80 opacity-0 transition-all duration-200 ease-out group-hover:ml-2 group-hover:w-4 group-hover:opacity-100 group-focus-within:ml-2 group-focus-within:w-4 group-focus-within:opacity-100';
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-start justify-between gap-4">
            <div class="flex flex-col">
                <CardTitle>Vehicle</CardTitle>
                <CardDescription>
                    {{ canEdit ? 'Manage vehicle details. Saving changes sends the vehicle back for verification.' : 'View vehicle details.' }}
                </CardDescription>
            </div>
            <div v-if="canEdit" class="flex flex-row items-center gap-2">
                <Button
                    :variant="!form.isDirty || form.processing ? 'disabled' : 'float'"
                    :disabled="!form.isDirty || form.processing"
                    @click="emit('reset')"
                >
                    Cancel
                </Button>
                <Button
                    :variant="!form.isDirty || form.processing ? 'disabled' : 'float-primary'"
                    size="icon-text"
                    :disabled="!form.isDirty || form.processing"
                    @click="emit('submit')"
                >
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </Button>
            </div>
        </CardHeader>

        <CardContent class="flex flex-row gap-4">
            <div class="max-w-1/3 flex-1">
                <div class="my-2 flex aspect-4/3 items-center justify-center rounded-md border border-dashed border-custom-bg-dark bg-custom-bg text-custom-shadow/70 dark:border-custom-bg-light dark:bg-custom-bg-dark">
                    <RiBusLine class="h-16 w-16 shrink-0" />
                </div>

                <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                    <div class="flex flex-row items-center justify-between gap-2 overflow-hidden">
                        <div class="inline-flex shrink-0 items-center gap-2">
                            <RiHashtag class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                            <span>Plate No.</span>
                        </div>
                        <span class="mr-1 truncate rounded-md bg-custom-bg px-2 font-mono font-normal tracking-widest uppercase dark:bg-custom-bg-light">
                            {{ vehicle.plate_number }}
                        </span>
                    </div>
                </div>
            </div>

            <Separator orientation="vertical" />

            <div class="min-w-0 flex-1">
                <CardSeparator title="Vehicle Info" />

                <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                    <div class="group flex flex-row items-center justify-between gap-2 overflow-hidden">
                        <div class="inline-flex shrink-0 items-center gap-2">
                            <RiBusLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                            <Label for="vehicle_type_id">Vehicle Type</Label>
                        </div>
                        <EditableField :editable="canEdit">
                            <template #edit>
                                <span class="flex min-w-0 flex-row items-center">
                                    <Select :model-value="form.vehicle_type_id ?? undefined" @update:model-value="onVehicleTypeChange">
                                        <SelectTrigger id="vehicle_type_id" variant="inline-edit">
                                            <SelectValue placeholder="Select a vehicle type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem v-for="type in vehicleTypes" :key="type.id" :value="type.id">
                                                    {{ type.type_name }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </span>
                            </template>
                            <span class="min-w-0 flex-1 truncate text-right text-sm font-semibold">{{ vehicleTypeName }}</span>
                        </EditableField>
                        <InputMessage v-if="canEdit" variant="destructive" :message="form.errors.vehicle_type_id" />
                    </div>

                    <div
                        v-for="field in vehicleFields"
                        :key="field.key"
                        class="group flex flex-row items-center justify-between gap-2 overflow-hidden"
                    >
                        <div class="inline-flex shrink-0 items-center gap-2">
                            <component :is="field.icon" class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                            <Label :for="`vehicle_${field.key}`">{{ field.label }}</Label>
                        </div>
                        <EditableField :editable="canEdit">
                            <template #edit>
                                <span class="flex min-w-0 flex-1 flex-row items-center">
                                    <Input
                                        :id="`vehicle_${field.key}`"
                                        :model-value="String(form[field.key] ?? '')"
                                        :type="field.type ?? 'text'"
                                        :placeholder="field.placeholder"
                                        variant="inline-edit"
                                        @update:model-value="setField(field.key, $event, field.format)"
                                    />
                                    <RiEditLine :class="editIconClass" />
                                </span>
                            </template>
                            <span class="min-w-0 flex-1 truncate text-right text-sm font-semibold">{{ savedValue(field.key) }}</span>
                        </EditableField>
                        <InputMessage v-if="canEdit" variant="destructive" :message="form.errors[field.key]" />
                    </div>

                    <div
                        v-for="field in identificationFields"
                        :key="field.key"
                        class="group flex flex-row items-center justify-between gap-2 overflow-hidden"
                    >
                        <div class="inline-flex shrink-0 items-center gap-2">
                            <component :is="field.icon" class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                            <Label :for="`vehicle_${field.key}`">{{ field.label }}</Label>
                        </div>
                        <EditableField :editable="canEdit">
                            <template #edit>
                                <span class="flex min-w-0 flex-1 flex-row items-center">
                                    <Input
                                        :id="`vehicle_${field.key}`"
                                        :model-value="String(form[field.key] ?? '')"
                                        :placeholder="field.placeholder"
                                        variant="inline-edit"
                                        @update:model-value="setField(field.key, $event, field.format)"
                                    />
                                    <RiEditLine :class="editIconClass" />
                                </span>
                            </template>
                            <span class="min-w-0 flex-1 truncate text-right text-sm font-semibold">{{ savedValue(field.key) }}</span>
                        </EditableField>
                        <InputMessage v-if="canEdit" variant="destructive" :message="form.errors[field.key]" />
                    </div>
                </div>

                <CardSeparator title="Operational Info" />

                <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                    <RouteField
                        v-model="form.route_id"
                        :routes="routes"
                        :map-config="mapConfig"
                        :can-edit="canEdit"
                        :error="form.errors.route_id"
                    />
                </div>

                <CardSeparator title="Others" />

                <div class="mt-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                    <div class="flex flex-row items-center justify-between overflow-hidden">
                        <div class="inline-flex shrink-0 items-center gap-2">
                            <RiCalendarLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                            <span>Registered</span>
                        </div>
                        <span class="min-w-0 flex-1 truncate text-right">{{ formatDate(vehicle.created_at) }}</span>
                    </div>
                    <div class="flex flex-row items-center justify-between overflow-hidden">
                        <div class="inline-flex shrink-0 items-center gap-2">
                            <RiCalendarLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                            <span>Last Updated</span>
                        </div>
                        <span class="min-w-0 flex-1 truncate text-right">{{ formatDateTime(vehicle.updated_at) }}</span>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
