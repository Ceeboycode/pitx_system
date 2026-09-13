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
import {
    BadgeCheck,
    Bus,
    Hash,
    Palette,
    Settings2,
    Shield,
    Users,
} from 'lucide-vue-next'

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

const presetCapacities = ['12', '30', '40', '60']

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

function capacitySelectValue() {
    return presetCapacities.includes(String(props.form.capacity))
        ? String(props.form.capacity)
        : 'custom'
}

function updateCapacity(value: string) {
    props.form.capacity = value === 'custom' ? '' : value
}
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-2xl border bg-muted/20 p-4 md:p-5">
            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                <div class="space-y-1">
                    <h3 class="text-base font-semibold tracking-tight text-foreground">
                        Vehicle Information
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        Enter the registration and identification details of the vehicle.
                    </p>
                </div>

                <div
                    v-if="readonly"
                    class="inline-flex w-fit items-center gap-2 rounded-full border bg-background px-3 py-1 text-xs font-medium text-muted-foreground"
                >
                    <BadgeCheck class="h-3.5 w-3.5" />
                    Read only
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border bg-background p-4 shadow-sm md:p-5">
                <div class="mb-4 flex items-center gap-2">
                    <div class="rounded-xl bg-red-50 p-2 text-red-600 ring-1 ring-red-100">
                        <Bus class="h-4 w-4" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-foreground">
                            Basic Details
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Main vehicle registration information.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4">
                    <div class="space-y-2">
                        <Label
                            for="vehicle_type"
                            class="text-xs font-semibold uppercase tracking-wide text-muted-foreground"
                        >
                            Vehicle Type
                        </Label>

                        <Select v-model="form.vehicle_type" :disabled="readonly">
                            <SelectTrigger
                                id="vehicle_type"
                                class="h-11 w-full rounded-xl border-muted-foreground/20 bg-background"
                            >
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
                        <Label
                            for="plate_number"
                            class="text-xs font-semibold uppercase tracking-wide text-muted-foreground"
                        >
                            Plate Number
                        </Label>

                        <div class="relative">
                            <Hash
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="plate_number"
                                :model-value="form.plate_number"
                                :disabled="readonly"
                                placeholder="Enter plate number"
                                class="h-11 rounded-xl pl-9"
                                @input="updatePlateNumber"
                            />
                        </div>

                        <InputError :message="form.errors.plate_number" />
                    </div>

                    <div class="space-y-2">
                        <Label
                            for="body_number"
                            class="text-xs font-semibold uppercase tracking-wide text-muted-foreground"
                        >
                            Body Number / Unit Number
                        </Label>

                        <div class="relative">
                            <Hash
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="body_number"
                                :model-value="form.body_number"
                                :disabled="readonly"
                                placeholder="Enter unit number"
                                class="h-11 rounded-xl pl-9"
                                @input="updateBodyNumber"
                            />
                        </div>

                        <InputError :message="form.errors.body_number" />
                    </div>

                    <div class="space-y-2">
                        <Label
                            for="capacity_select"
                            class="text-xs font-semibold uppercase tracking-wide text-muted-foreground"
                        >
                            Capacity
                        </Label>

                        <Select
                            :model-value="capacitySelectValue()"
                            :disabled="readonly"
                            @update:model-value="updateCapacity"
                        >
                            <SelectTrigger
                                id="capacity_select"
                                class="h-11 w-full rounded-xl border-muted-foreground/20 bg-background"
                            >
                                <SelectValue placeholder="Select capacity" />
                            </SelectTrigger>

                            <SelectContent>
                                <SelectItem value="12">12</SelectItem>
                                <SelectItem value="30">30</SelectItem>
                                <SelectItem value="40">40</SelectItem>
                                <SelectItem value="60">60</SelectItem>
                                <SelectItem value="custom">Custom</SelectItem>
                            </SelectContent>
                        </Select>

                        <div
                            v-if="capacitySelectValue() === 'custom'"
                            class="relative"
                        >
                            <Users
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="custom_capacity"
                                v-model="form.capacity"
                                :disabled="readonly"
                                type="number"
                                min="1"
                                placeholder="Enter seating capacity"
                                class="h-11 rounded-xl pl-9"
                            />
                        </div>

                        <InputError :message="form.errors.capacity" />
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border bg-background p-4 shadow-sm md:p-5">
                <div class="mb-4 flex items-center gap-2">
                    <div class="rounded-xl bg-blue-50 p-2 text-blue-700 ring-1 ring-blue-100">
                        <Settings2 class="h-4 w-4" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-foreground">
                            Technical Details
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Appearance, model, and identification numbers.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4">
                    <div class="space-y-2">
                        <Label
                            for="color"
                            class="text-xs font-semibold uppercase tracking-wide text-muted-foreground"
                        >
                            Color
                        </Label>

                        <div class="relative">
                            <Palette
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="color"
                                :model-value="form.color"
                                :disabled="readonly"
                                placeholder="Enter color"
                                class="h-11 rounded-xl pl-9"
                                @input="updateColor"
                            />
                        </div>

                        <InputError :message="form.errors.color" />
                    </div>

                    <div class="space-y-2">
                        <Label
                            for="make_model"
                            class="text-xs font-semibold uppercase tracking-wide text-muted-foreground"
                        >
                            Make / Model
                        </Label>

                        <div class="relative">
                            <Bus
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="make_model"
                                :model-value="form.make_model"
                                :disabled="readonly"
                                placeholder="Enter make / model"
                                class="h-11 rounded-xl pl-9"
                                @input="updateMakeModel"
                            />
                        </div>

                        <InputError :message="form.errors.make_model" />
                    </div>

                    <div class="space-y-2">
                        <Label
                            for="engine_number"
                            class="text-xs font-semibold uppercase tracking-wide text-muted-foreground"
                        >
                            Engine Number
                        </Label>

                        <div class="relative">
                            <Shield
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="engine_number"
                                :model-value="form.engine_number"
                                :disabled="readonly"
                                placeholder="Enter engine number"
                                class="h-11 rounded-xl pl-9"
                                @input="updateEngineNumber"
                            />
                        </div>

                        <InputError :message="form.errors.engine_number" />
                    </div>

                    <div class="space-y-2">
                        <Label
                            for="chassis_number"
                            class="text-xs font-semibold uppercase tracking-wide text-muted-foreground"
                        >
                            Chassis Number
                        </Label>

                        <div class="relative">
                            <Shield
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="chassis_number"
                                :model-value="form.chassis_number"
                                :disabled="readonly"
                                placeholder="Enter chassis number"
                                class="h-11 rounded-xl pl-9"
                                @input="updateChassisNumber"
                            />
                        </div>

                        <InputError :message="form.errors.chassis_number" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>