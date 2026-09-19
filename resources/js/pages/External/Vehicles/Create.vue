<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

import ExternalLayout from '@/layouts/ExternalLayout.vue'

import VehicleBasicInfoForm from '@/components/internal/company/vehicles/VehicleBasicInfoForm.vue'
import VehicleDocumentsForm from '@/components/internal/company/vehicles/VehicleDocumentsForm.vue'
import VehicleRouteAssignment from '@/components/internal/company/vehicles/VehicleRouteAssignment.vue'
import VehicleSummaryCard from '@/components/internal/company/vehicles/VehicleSummaryCard.vue'

import { Button } from '@/components/ui/button'

import CompanyVehicleController from '@/actions/App/Http/Controllers/CompanyVehicleController'

import { ArrowUp } from 'lucide-vue-next'
import {
    RiArrowLeftLine,
    RiBuilding2Line,
    RiBusLine,
    RiFileTextLine,
    RiMapPin2Line,
} from 'vue-remix-icons'

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
    vehicleTypes: Array<{id: number, type_name: string}>
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

const requiredDocumentsCount = computed(() => form.documents.length)

const uploadedDocumentsCount = computed(() =>
    form.documents.filter((doc) => doc.file !== null).length,
)

function setDocumentFile(index: number, event: Event) {
    const input = event.target as HTMLInputElement
    form.documents[index].file = input.files?.[0] ?? null
}

function scrollToSummary() {
    const summarySection = document.getElementById('submission-summary')

    if (summarySection) {
        summarySection.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        })
    }
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
        <div class="min-h-screen bg-slate-50/60">
            <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-slate-400">
                            <RiBuilding2Line class="h-3.5 w-3.5 shrink-0" />
                            {{ company.company_code ?? company.company_name }}
                            <span class="text-slate-300">·</span>
                            <span>Vehicles</span>
                            <span class="text-slate-300">·</span>
                            <span>Register</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-700">
                                <RiBusLine class="h-4 w-4 shrink-0 text-white" />
                            </div>
                            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Register Vehicle</h1>
                        </div>
                        <p class="text-sm text-slate-500">
                            Add a vehicle, assign a route, and upload the required documents.
                        </p>
                    </div>

                    <Button
                        as-child
                        variant="outline"
                        class="shrink-0 self-start rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-800"
                    >
                        <Link :href="CompanyVehicleController.index().url">
                            <RiArrowLeftLine class="mr-2 h-4 w-4 shrink-0" />
                            Back to Vehicles
                        </Link>
                    </Button>
                </div>

                <form class="space-y-6" @submit.prevent="submit">
                    <div class="grid gap-6 xl:grid-cols-[1fr_320px]">
                        <div class="space-y-6">
                            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                                <div class="border-b border-slate-100 px-6 py-4">
                                    <h2 class="text-base font-semibold text-slate-800">Company Information</h2>
                                    <p class="mt-0.5 text-xs text-slate-400">This vehicle will be registered under your company account.</p>
                                </div>
                                <div class="grid gap-4 p-6 md:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Company</p>
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700">
                                            {{ company.company_name }}
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Company Code</p>
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 font-mono text-sm font-semibold text-slate-700">
                                            {{ company.company_code ?? '—' }}
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Representative</p>
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700">
                                            {{ user.name }}
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Account Email</p>
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700">
                                            {{ user.email }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                                <div class="border-b border-slate-100 px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-md bg-sky-100">
                                            <RiMapPin2Line class="h-3.5 w-3.5 shrink-0 text-sky-600" />
                                        </div>
                                        <h2 class="text-base font-semibold text-slate-800">Route Assignment</h2>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-400">Select the operating route for this vehicle.</p>
                                </div>
                                <div class="p-6">
                                    <VehicleRouteAssignment
                                        v-model="form.route_id"
                                        :routes="routes"
                                        :gates="gates"
                                        :error="form.errors.route_id"
                                        :map-config="mapConfig"
                                    />
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                                <div class="border-b border-slate-100 px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-md bg-blue-100">
                                            <RiBusLine class="h-3.5 w-3.5 shrink-0 text-blue-700" />
                                        </div>
                                        <h2 class="text-base font-semibold text-slate-800">Vehicle Information</h2>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-400">Enter the primary details of the vehicle.</p>
                                </div>
                                <div class="p-6">
                                    <VehicleBasicInfoForm
                                    :form="form"
                                    :vehicle-type-name="vehicleTypes.find((type) => type.id === form.vehicle_type_id)?.type_name"
                                        :vehicle-types="vehicleTypes"
                                    />
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                                <div class="border-b border-slate-100 px-6 py-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <div class="flex h-6 w-6 items-center justify-center rounded-md bg-violet-100">
                                                    <RiFileTextLine class="h-3.5 w-3.5 shrink-0 text-violet-600" />
                                                </div>
                                                <h2 class="text-base font-semibold text-slate-800">Required Documents</h2>
                                            </div>
                                            <p class="mt-1 text-xs text-slate-400">
                                                Upload all required supporting files for this vehicle, including a clear bus photo for PUV identification markings.
                                            </p>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-semibold tabular-nums text-slate-400">
                                                {{ uploadedDocumentsCount }} / {{ requiredDocumentsCount }} uploaded
                                            </span>

                                            <Button
                                                type="button"
                                                variant="outline"
                                                class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-800"
                                                @click="scrollToSummary"
                                            >
                                                <ArrowUp class="mr-2 h-4 w-4 shrink-0" />
                                                View Summary
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-6">
                                    <VehicleDocumentsForm
                                        :documents="form.documents"
                                        :doc-types="docTypes"
                                        :errors="form.errors"
                                        @set-file="setDocumentFile"
                                    />

                                    <div class="mt-6 flex justify-end">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-800"
                                            @click="scrollToSummary"
                                        >
                                            <ArrowUp class="mr-2 h-4 w-4 shrink-0" />
                                            Go to Submission Summary
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div
                                id="submission-summary"
                                class="rounded-xl border border-slate-200 bg-white shadow-sm"
                            >
                                <div class="border-b border-slate-100 px-5 py-4">
                                    <h3 class="text-sm font-semibold text-slate-800">Submission Summary</h3>
                                    <p class="mt-0.5 text-xs text-slate-400">Review the important details before saving.</p>
                                </div>
                                <div class="p-5">
                                    <VehicleSummaryCard
                                        :form="form"
                                        :selected-route-name="selectedRoute?.route_name"
                                        :required-documents-count="requiredDocumentsCount"
                                        :user-name="user.name"
                                        submit-label="Register Vehicle"
                                    />
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                                <div class="border-b border-slate-100 px-5 py-4">
                                    <h3 class="text-sm font-semibold text-slate-800">Registration Notes</h3>
                                    <p class="mt-0.5 text-xs text-slate-400">Keep these in mind before submitting.</p>
                                </div>
                                <div class="divide-y divide-slate-100">
                                    <div class="flex gap-3 px-5 py-4">
                                        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-sky-100">
                                            <RiMapPin2Line class="h-3.5 w-3.5 shrink-0 text-sky-600" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-widest text-sky-700">Route Assignment</p>
                                            <p class="mt-0.5 text-xs text-slate-500">
                                                Make sure the selected route matches the vehicle's intended operating line.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex gap-3 px-5 py-4">
                                        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-violet-100">
                                            <RiFileTextLine class="h-3.5 w-3.5 shrink-0 text-violet-600" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-widest text-violet-700">Required Documents</p>
                                            <p class="mt-0.5 text-xs text-slate-500">
                                                Upload Insurance Certificate, CPC, OR, CR, and a PUV identification markings photo before submission.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex gap-3 px-5 py-4">
                                        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-blue-100">
                                            <RiBusLine class="h-3.5 w-3.5 shrink-0 text-blue-700" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-widest text-blue-700">Vehicle Details</p>
                                            <p class="mt-0.5 text-xs text-slate-500">
                                                Plate number and engine details must match official registration records.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </ExternalLayout>
</template>

