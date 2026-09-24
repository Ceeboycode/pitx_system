<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import type { AcceptableValue } from 'reka-ui'

import ExternalLayout from '@/layouts/ExternalLayout.vue'

import VehicleDocumentsForm from '@/components/internal/company/vehicles/VehicleDocumentsForm.vue'
import VehicleRouteAssignment from '@/components/internal/company/vehicles/VehicleRouteAssignment.vue'
import { VehicleReviewCard } from '@/components/external/vehicle'

import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { InputMessage } from '@/components/ui/_input-message'
import { CardSeparator } from '@/components/ui/_card-separator'
import { LeadingCard } from '@/components/ui/_leading-card'
import { LeadPanel, MainPanel, PanelLayout, SidePanel } from '@/components/ui/_panels'

import CompanyVehicleController from '@/actions/App/Http/Controllers/CompanyVehicleController'

type Company = {
    id: number
    company_name: string
    company_code?: string | null
    status: string
    logo_url?: string | null
}

type User = {
    id: number
    name: string
    username: string
    email: string
}

type GateItem = {
    id: number
    gate_name: string
    bays?: number | null
}

type RouteStop = {
    id: number
    route_id: number
    stop_name: string
    stop_order: number
    stop_type: string
    address?: string | null
    latitude?: number | null
    longitude?: number | null
}

type RouteItem = {
    id: number
    gate_id?: number | null
    route_name: string
    origin_name?: string | null
    destination_name?: string | null
    route_geometry?: unknown
    stops?: RouteStop[]
    gate?: {
        id: number
        gate_name: string
    } | null
}

type DocTypes = Record<string, string>

const props = defineProps<{
    company: Company
    user: User
    gates: GateItem[]
    routes: RouteItem[]
    docTypes: DocTypes
    vehicleTypes: Array<{ id: number; type_name: string }>
    mapConfig: {
        mapboxToken?: string | null
        defaultCenter: {
            lng: number
            lat: number
        }
        defaultZoom: number
    }
}>()

const form = useForm({
    vehicle_type_id: null as number | null,
    plate_number: '',
    body_number: '',
    capacity: '',
    color: '',
    engine_number: '',
    chassis_number: '',
    make_model: '',
    route_id: '',
    documents: Object.entries(props.docTypes).map(([key]) => ({
        document_type: key,
        file: null as File | null,
        issued_at: '',
        expires_at: '',
    })),
})

const selectedRoute = computed(() =>
    props.routes.find((route) => String(route.id) === String(form.route_id)) ?? null,
)

const vehicleTypeName = computed(
    () => props.vehicleTypes.find((type) => type.id === form.vehicle_type_id)?.type_name,
)

const requiredDocumentsCount = computed(() => form.documents.length)

const isFormValid = computed(
    () =>
        form.vehicle_type_id !== null &&
        form.plate_number.trim() !== '' &&
        form.body_number.trim() !== '' &&
        String(form.capacity).trim() !== '' &&
        form.color.trim() !== '' &&
        form.engine_number.trim() !== '' &&
        form.chassis_number.trim() !== '' &&
        form.make_model.trim() !== '' &&
        form.route_id !== '' &&
        form.documents.every((document) => document.file !== null),
)

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
    form.plate_number = toUppercase((event.target as HTMLInputElement).value)
}

function updateBodyNumber(event: Event) {
    form.body_number = toUppercase((event.target as HTMLInputElement).value)
}

function updateEngineNumber(event: Event) {
    form.engine_number = toUppercase((event.target as HTMLInputElement).value)
}

function updateChassisNumber(event: Event) {
    form.chassis_number = toUppercase((event.target as HTMLInputElement).value)
}

function updateMakeModel(event: Event) {
    form.make_model = toTitleCase((event.target as HTMLInputElement).value)
}

function updateColor(event: Event) {
    form.color = toNormalCase((event.target as HTMLInputElement).value)
}

function capacitySelectValue() {
    return presetCapacities.includes(String(form.capacity)) ? String(form.capacity) : 'custom'
}

function updateCapacity(value: AcceptableValue) {
    const normalizedValue = String(value ?? '')
    form.capacity = normalizedValue === 'custom' ? '' : normalizedValue
}

function setDocumentFile(index: number, file: File | null) {
    form.documents[index].file = file
}

function submit() {
    form.post(CompanyVehicleController.store().url, {
        forceFormData: true,
    })
}
</script>

<template>
    <Head title="Register Vehicle" />

    <ExternalLayout :company="company" :user="user">
        <PanelLayout>
            <MainPanel>
                <LeadPanel class="h-fit p-0">
                    <LeadingCard
                        title="Register vehicle"
                        description="Add a new vehicle, assign a route, and upload the required documents."
                        variant="entity-crud"
                        :back="CompanyVehicleController.index().url"
                        :more="false"
                    />
                </LeadPanel>

                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                        <CardDescription>Fields with <span class="text-destructive font-semibold">*</span> are required.</CardDescription>
                    </CardHeader>

                    <CardContent class="flex flex-col gap-2">
                        <CardSeparator title="Basic Details" />

                        <div class="my-2 grid gap-x-4 gap-y-2 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="vehicle_type_id">Vehicle Type</Label>
                                <Select v-model="form.vehicle_type_id">
                                    <SelectTrigger id="vehicle_type_id" class="w-full">
                                        <SelectValue placeholder="Select vehicle type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="type in vehicleTypes" :key="type.id" :value="type.id">
                                            {{ type.type_name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputMessage variant="destructive" :message="form.errors.vehicle_type_id" class="mt-0" />
                            </div>

                            <div class="space-y-2">
                                <Label for="plate_number">Plate Number</Label>
                                <Input
                                    id="plate_number"
                                    :model-value="form.plate_number"
                                    placeholder="Enter plate number"
                                    @input="updatePlateNumber"
                                />
                                <InputMessage variant="destructive" :message="form.errors.plate_number" class="mt-0" />
                            </div>

                            <div class="space-y-2">
                                <Label for="body_number">Body Number / Unit Number</Label>
                                <Input
                                    id="body_number"
                                    :model-value="form.body_number"
                                    placeholder="Enter unit number"
                                    @input="updateBodyNumber"
                                />
                                <InputMessage variant="destructive" :message="form.errors.body_number" class="mt-0" />
                            </div>

                            <div class="space-y-2">
                                <Label for="capacity_select">Capacity</Label>
                                <Select :model-value="capacitySelectValue()" @update:model-value="updateCapacity">
                                    <SelectTrigger id="capacity_select" class="w-full">
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
                                <Input
                                    v-if="capacitySelectValue() === 'custom'"
                                    id="custom_capacity"
                                    v-model="form.capacity"
                                    type="number"
                                    min="1"
                                    placeholder="Enter seating capacity"
                                />
                                <InputMessage variant="destructive" :message="form.errors.capacity" class="mt-0" />
                            </div>
                        </div>

                        <CardSeparator title="Technical Details" />

                        <div class="my-2 grid gap-x-4 gap-y-2 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="color">Color</Label>
                                <Input id="color" :model-value="form.color" placeholder="Enter color" @input="updateColor" />
                                <InputMessage variant="destructive" :message="form.errors.color" class="mt-0" />
                            </div>

                            <div class="space-y-2">
                                <Label for="make_model">Make / Model</Label>
                                <Input id="make_model" :model-value="form.make_model" placeholder="Enter make / model" @input="updateMakeModel" />
                                <InputMessage variant="destructive" :message="form.errors.make_model" class="mt-0" />
                            </div>

                            <div class="space-y-2">
                                <Label for="engine_number">Engine Number</Label>
                                <Input id="engine_number" :model-value="form.engine_number" placeholder="Enter engine number" @input="updateEngineNumber" />
                                <InputMessage variant="destructive" :message="form.errors.engine_number" class="mt-0" />
                            </div>

                            <div class="space-y-2">
                                <Label for="chassis_number">Chassis Number</Label>
                                <Input id="chassis_number" :model-value="form.chassis_number" placeholder="Enter chassis number" @input="updateChassisNumber" />
                                <InputMessage variant="destructive" :message="form.errors.chassis_number" class="mt-0" />
                            </div>
                        </div>

                        <CardSeparator title="Route Assignment" />

                        <div class="my-2">
                            <VehicleRouteAssignment
                                v-model="form.route_id"
                                :routes="routes"
                                :gates="gates"
                                :error="form.errors.route_id"
                                :map-config="mapConfig"
                                :gate-selectable="false"
                            />
                        </div>

                        <CardSeparator title="Documents" />

                        <div class="my-2">
                            <VehicleDocumentsForm
                                :documents="form.documents"
                                :doc-types="docTypes"
                                :errors="form.errors"
                                @set-file="setDocumentFile"
                            />
                        </div>
                    </CardContent>

                    <CardFooter class="justify-end gap-2 pt-4">
                        <Button type="button" variant="float" as-child>
                            <Link :href="CompanyVehicleController.index().url">Cancel</Link>
                        </Button>

                        <Button
                            type="button"
                            :variant="form.processing || !isFormValid ? 'disabled' : 'float-primary'"
                            :disabled="form.processing || !isFormValid"
                            @click="submit"
                        >
                            {{ form.processing ? 'Registering...' : 'Register Vehicle' }}
                        </Button>
                    </CardFooter>
                </Card>
            </MainPanel>

            <SidePanel>
                <VehicleReviewCard
                    :values="form"
                    :vehicle-type-name="vehicleTypeName"
                    :selected-route-name="selectedRoute?.route_name"
                    :required-documents-count="requiredDocumentsCount"
                    :user-name="user.name"
                />
            </SidePanel>
        </PanelLayout>
    </ExternalLayout>
</template>
