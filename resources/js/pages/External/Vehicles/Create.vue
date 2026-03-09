<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

import ExternalLayout from '@/layouts/ExternalLayout.vue'

import VehicleBasicInfoForm from '@/components/company/vehicles/VehicleBasicInfoForm.vue'
import VehicleDocumentsForm from '@/components/company/vehicles/VehicleDocumentsForm.vue'
import VehicleRouteAssignment from '@/components/company/vehicles/VehicleRouteAssignment.vue'
import VehicleSummaryCard from '@/components/company/vehicles/VehicleSummaryCard.vue'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'

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
    mapConfig: {
        mapboxToken?: string | null
        defaultCenter: {
            lng: number
            lat: number
        }
        defaultZoom: number
    }
}>()

const vehicleTypes = [
    'Bus',
    'Modern Jeepney',
    'Jeepney',
    'Mini Bus',
    'UV Express',
    'Van',
]

const form = useForm({
    vehicle_type: '',
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

const selectedRoute = computed(() => {
    return (
        props.routes.find(
            (route) => String(route.id) === String(form.route_id),
        ) ?? null
    )
})

const requiredDocumentsCount = computed(() => form.documents.length)

function setDocumentFile(index: number, event: Event) {
    const input = event.target as HTMLInputElement
    form.documents[index].file = input.files?.[0] ?? null
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
        <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <Badge variant="secondary">Vehicles</Badge>
                        <Badge variant="outline">Create</Badge>
                    </div>

                    <h1 class="text-2xl font-semibold tracking-tight">
                        Register Vehicle
                    </h1>

                    <p class="text-sm text-muted-foreground">
                        Add a vehicle, assign a route, and upload the required documents.
                    </p>
                </div>

                <Button as-child variant="outline">
                    <Link :href="CompanyVehicleController.index().url">
                        Back to Vehicles
                    </Link>
                </Button>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <div class="grid gap-6 xl:grid-cols-[1fr_320px]">
                    <div class="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Company Information</CardTitle>
                                <CardDescription>
                                    This vehicle will be registered under your company account.
                                </CardDescription>
                            </CardHeader>

                            <CardContent class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <p class="text-sm font-medium">Company</p>
                                    <p class="rounded-md border px-3 py-2 text-sm">
                                        {{ company.company_name }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm font-medium">Company Code</p>
                                    <p class="rounded-md border px-3 py-2 text-sm">
                                        {{ company.company_code ?? '—' }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm font-medium">Representative</p>
                                    <p class="rounded-md border px-3 py-2 text-sm">
                                        {{ user.name }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm font-medium">Account Email</p>
                                    <p class="rounded-md border px-3 py-2 text-sm">
                                        {{ user.email }}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Route Assignment</CardTitle>
                                <CardDescription>
                                    Select the operating route for this vehicle.
                                </CardDescription>
                            </CardHeader>

                            <CardContent>
                                <VehicleRouteAssignment
                                    v-model="form.route_id"
                                    :routes="routes"
                                    :gates="gates"
                                    :error="form.errors.route_id"
                                    :map-config="mapConfig"
                                />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Vehicle Information</CardTitle>
                                <CardDescription>
                                    Enter the primary details of the vehicle.
                                </CardDescription>
                            </CardHeader>

                            <CardContent>
                                <VehicleBasicInfoForm
                                    :form="form"
                                    :vehicle-types="vehicleTypes"
                                />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Required Documents</CardTitle>
                                <CardDescription>
                                    Upload all required supporting files for this vehicle.
                                </CardDescription>
                            </CardHeader>

                            <CardContent>
                                <VehicleDocumentsForm
                                    :documents="form.documents"
                                    :doc-types="docTypes"
                                    :errors="form.errors"
                                    @set-file="setDocumentFile"
                                />
                            </CardContent>
                        </Card>
                    </div>

                    <div class="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Submission Summary</CardTitle>
                                <CardDescription>
                                    Review the important details before saving.
                                </CardDescription>
                            </CardHeader>

                            <CardContent>
                                <VehicleSummaryCard
                                    :form="form"
                                    :selected-route-name="selectedRoute?.route_name"
                                    :required-documents-count="requiredDocumentsCount"
                                    :user-name="user.name"
                                    submit-label="Register Vehicle"
                                />
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </form>
        </div>
    </ExternalLayout>
</template>
