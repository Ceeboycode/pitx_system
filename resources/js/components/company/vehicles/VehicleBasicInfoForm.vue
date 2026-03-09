<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'

type VehicleFormShape = {
    vehicle_type: string
    plate_number: string
    body_number: string
    capacity: string | number
    color: string
    engine_number: string
    chassis_number: string
    make_model: string
    errors: Record<string, string>
}

const props = defineProps<{
    form: VehicleFormShape
    vehicleTypes: string[]
    readonly?: boolean
}>()

function toUppercase(value: string | number | null | undefined) {
    return String(value ?? '').toUpperCase()
}

function toTitleCase(value: string | number | null | undefined) {
    return String(value ?? '')
        .toLowerCase()
        .replace(/\b\w/g, (char) => char.toUpperCase())
}

function toNormalCase(value: string | number | null | undefined) {
    const text = String(value ?? '').trimStart()

    if (!text) return ''

    return text.charAt(0).toUpperCase() + text.slice(1).toLowerCase()
}

function updatePlateNumber(event: Event) {
    props.form.plate_number = toUppercase(
        (event.target as HTMLInputElement).value,
    )
}

function updateBodyNumber(event: Event) {
    props.form.body_number = toUppercase(
        (event.target as HTMLInputElement).value,
    )
}

function updateEngineNumber(event: Event) {
    props.form.engine_number = toUppercase(
        (event.target as HTMLInputElement).value,
    )
}

function updateChassisNumber(event: Event) {
    props.form.chassis_number = toUppercase(
        (event.target as HTMLInputElement).value,
    )
}

function updateMakeModel(event: Event) {
    props.form.make_model = toTitleCase(
        (event.target as HTMLInputElement).value,
    )
}

function updateColor(event: Event) {
    props.form.color = toNormalCase(
        (event.target as HTMLInputElement).value,
    )
}
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
            <Label for="vehicle_type">Vehicle Type</Label>
            <Select v-model="form.vehicle_type" :disabled="readonly">
                <SelectTrigger id="vehicle_type" class="w-full">
                    <SelectValue placeholder="Select vehicle type" />
                </SelectTrigger>

                <SelectContent>
                    <SelectItem
                        v-for="type in vehicleTypes"
                        :key="type"
                        :value="type"
                    >
                        {{ type }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.vehicle_type" />
        </div>

        <div class="space-y-2">
            <Label for="plate_number">Plate Number</Label>
            <Input
                id="plate_number"
                :model-value="form.plate_number"
                :disabled="readonly"
                placeholder="Enter plate number"
                @input="updatePlateNumber"
            />
            <InputError :message="form.errors.plate_number" />
        </div>

        <div class="space-y-2">
            <Label for="body_number">Body Number / Unit Number</Label>
            <Input
                id="body_number"
                :model-value="form.body_number"
                :disabled="readonly"
                placeholder="Enter unit number"
                @input="updateBodyNumber"
            />
            <InputError :message="form.errors.body_number" />
        </div>

        <div class="space-y-2">
            <Label for="capacity">Capacity</Label>
            <Input
                id="capacity"
                v-model="form.capacity"
                :disabled="readonly"
                type="number"
                min="1"
                placeholder="Enter seating capacity"
            />
            <InputError :message="form.errors.capacity" />
        </div>

        <div class="space-y-2">
            <Label for="color">Color</Label>
            <Input
                id="color"
                :model-value="form.color"
                :disabled="readonly"
                placeholder="Enter color"
                @input="updateColor"
            />
            <InputError :message="form.errors.color" />
        </div>

        <div class="space-y-2">
            <Label for="make_model">Make / Model</Label>
            <Input
                id="make_model"
                :model-value="form.make_model"
                :disabled="readonly"
                placeholder="Enter make / model"
                @input="updateMakeModel"
            />
            <InputError :message="form.errors.make_model" />
        </div>

        <div class="space-y-2">
            <Label for="engine_number">Engine Number</Label>
            <Input
                id="engine_number"
                :model-value="form.engine_number"
                :disabled="readonly"
                placeholder="Enter engine number"
                @input="updateEngineNumber"
            />
            <InputError :message="form.errors.engine_number" />
        </div>

        <div class="space-y-2">
            <Label for="chassis_number">Chassis Number</Label>
            <Input
                id="chassis_number"
                :model-value="form.chassis_number"
                :disabled="readonly"
                placeholder="Enter chassis number"
                @input="updateChassisNumber"
            />
            <InputError :message="form.errors.chassis_number" />
        </div>
    </div>
</template>
