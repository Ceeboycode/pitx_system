<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import ExternalLayout from '@/layouts/ExternalLayout.vue';

import VehicleBasicInfoForm from '@/components/company/vehicles/VehicleBasicInfoForm.vue';
import VehicleDocumentsForm from '@/components/company/vehicles/VehicleDocumentsForm.vue';
import VehicleRouteAssignment from '@/components/company/vehicles/VehicleRouteAssignment.vue';
import VehicleSummaryCard from '@/components/company/vehicles/VehicleSummaryCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    AlertCircle,
    ArrowLeft,
    ArrowUp,
    Eye,
    FileText,
    Hash,
    MapPin,
} from 'lucide-vue-next';

import CompanyVehicleController from '@/actions/App/Http/Controllers/CompanyVehicleController';

type Company = {
    id: number;
    company_name: string;
    company_code?: string | null;
    status: string;
    logo_url?: string | null;
};

type User = {
    id: number;
    name: string;
    username: string;
    email: string;
};

type GateItem = {
    id: number;
    gate_name: string;
    bays?: number | null;
};

type RouteStop = {
    id: number;
    route_id: number;
    stop_name: string;
    stop_order: number;
    stop_type: string;
    address?: string | null;
    latitude?: number | null;
    longitude?: number | null;
};

type RouteItem = {
    id: number;
    gate_id?: number | null;
    route_name: string;
    origin_name?: string | null;
    destination_name?: string | null;
    route_geometry?: unknown;
    stops?: RouteStop[];
    gate?: {
        id: number;
        gate_name: string;
    } | null;
};

type VehicleDocument = {
    id: number;
    document_type: string;
    file_name?: string | null;
    status: string;
    issued_at?: string | null;
    expires_at?: string | null;
};

type Vehicle = {
    id: number;
    route_id: number | string | null;
    vehicle_type: string;
    plate_number: string;
    body_number: string;
    capacity: string | number;
    color: string;
    engine_number: string;
    chassis_number: string;
    make_model: string;
    status: string;
    documents: VehicleDocument[];
};

type DocTypes = Record<string, string>;

const props = defineProps<{
    company: Company;
    user: User;
    vehicle: Vehicle;
    gates: GateItem[];
    routes: RouteItem[];
    docTypes: DocTypes;
    mapConfig: {
        mapboxToken?: string | null;
        defaultCenter: {
            lng: number;
            lat: number;
        };
        defaultZoom: number;
    };
}>();

const vehicleTypes = [
    'Bus',
    'Modern Jeepney',
    'Jeepney',
    'Mini Bus',
    'UV Express',
    'Van',
];

const form = useForm({
    vehicle_type: props.vehicle.vehicle_type ?? '',
    plate_number: props.vehicle.plate_number ?? '',
    body_number: props.vehicle.body_number ?? '',
    capacity: props.vehicle.capacity ?? '',
    color: props.vehicle.color ?? '',
    engine_number: props.vehicle.engine_number ?? '',
    chassis_number: props.vehicle.chassis_number ?? '',
    make_model: props.vehicle.make_model ?? '',
    route_id: props.vehicle.route_id ? String(props.vehicle.route_id) : '',
    documents: Object.keys(props.docTypes).map((docType) => {
        const existing = props.vehicle.documents.find(
            (doc) => doc.document_type === docType,
        );
        return {
            id: existing?.id ?? null,
            document_type: docType,
            status: existing?.status ?? 'pending',
            existing_file_name: existing?.file_name ?? null,
            file: null as File | null,
            issued_at: existing?.issued_at ?? '',
            expires_at: existing?.expires_at ?? '',
        };
    }),
});

const selectedRoute = computed(
    () =>
        props.routes.find(
            (route) => String(route.id) === String(form.route_id),
        ) ?? null,
);

const requiredDocumentsCount = computed(
    () => Object.keys(props.docTypes).length,
);

const uploadedDocumentsCount = computed(
    () =>
        form.documents.filter((doc) => doc.existing_file_name || doc.file)
            .length,
);

const pendingDocumentsCount = computed(
    () => form.documents.filter((doc) => doc.status === 'pending').length,
);

function submit() {
    form.transform((data) => ({ ...data, _method: 'put' })).post(
        `/company/vehicles/${props.vehicle.id}`,
        {
            forceFormData: true,
            onFinish: () => {
                form.transform((data) => data);
            },
        },
    );
}

function setDocumentFile(index: number, event: Event) {
    const input = event.target as HTMLInputElement;
    form.documents[index].file = input.files?.[0] ?? null;
}

function scrollToSummary() {
    const summarySection = document.getElementById('update-summary');
    if (summarySection) {
        summarySection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function humanize(value?: string | null) {
    if (!value) return '—';
    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function statusClass(status?: string | null) {
    switch (status) {
        case 'active':
        case 'approved':
        case 'verified':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'pending':
        case 'for_verification':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'rejected':
        case 'inactive':
            return 'bg-rose-100 text-rose-600 border-rose-200';
        default:
            return 'bg-slate-100 text-slate-600 border-0';
    }
}

function statusDot(status?: string | null) {
    switch (status) {
        case 'active':
        case 'approved':
        case 'verified':
            return 'bg-emerald-500';
        case 'pending':
        case 'for_verification':
            return 'bg-amber-500';
        case 'rejected':
        case 'inactive':
            return 'bg-rose-500';
        default:
            return 'bg-slate-400';
    }
}
</script>

<template>
    <Head :title="`Edit Vehicle ${vehicle.plate_number}`" />

    <ExternalLayout :company="company" :user="user">
        <div class="min-h-screen bg-slate-50/60">
            <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-8">
                
                <Card>
                    <CardContent class="p-6">
                        <div
                            class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between"
                        >
                            <div class="flex items-start gap-4">
                                <div
                                    class="h-16 w-16 overflow-hidden rounded-2xl border bg-muted"
                                >
                                    <img
                                        v-if="company.logo_url"
                                        :src="company.logo_url"
                                        :alt="company.company_name"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center text-lg font-semibold"
                                    >
                                        {{
                                            (
                                                company.company_code ??
                                                company.company_name
                                            )
                                                .slice(0, 2)
                                                .toUpperCase()
                                        }}
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div>
                                        <p
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{
                                                company.company_code ??
                                                company.company_name
                                            }}
                                            · Vehicles · Edit
                                        </p>
                                        <h1
                                            class="text-2xl font-semibold tracking-tight"
                                        >
                                            Edit Vehicle
                                        </h1>
                                        <p
                                            class="text-sm text-muted-foreground"
                                        >
                                            Update vehicle details, route
                                            assignment, and required documents.
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <Badge
                                            :class="[
                                                'border font-medium',
                                                statusClass(vehicle.status),
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'mr-1.5 h-1.5 w-1.5 rounded-full',
                                                    statusDot(vehicle.status),
                                                ]"
                                            />
                                            {{ humanize(vehicle.status) }}
                                        </Badge>
                                        <Badge
                                            variant="outline"
                                            class="font-mono"
                                        >
                                            {{ vehicle.plate_number }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="flex shrink-0 flex-wrap items-center gap-2"
                            >
                                <Button as-child variant="outline">
                                    <Link
                                        :href="
                                            CompanyVehicleController.index().url
                                        "
                                    >
                                        <ArrowLeft class="mr-2 h-4 w-4" />
                                        Back
                                    </Link>
                                </Button>

                                <Button as-child>
                                    <Link
                                        :href="
                                            CompanyVehicleController.show(
                                                vehicle.id,
                                            ).url
                                        "
                                    >
                                        <Eye class="mr-2 h-4 w-4" />
                                        View Vehicle
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                
                <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <div
                            class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-blue-700"
                        >
                            <Hash class="h-4 w-4 text-white" />
                        </div>
                        <p
                            class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                        >
                            Plate Number
                        </p>
                        <p
                            class="mt-0.5 truncate font-mono text-lg font-bold text-slate-900"
                        >
                            {{ form.plate_number || '—' }}
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <div
                            class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-sky-600"
                        >
                            <MapPin class="h-4 w-4 text-white" />
                        </div>
                        <p
                            class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                        >
                            Selected Route
                        </p>
                        <p
                            class="mt-0.5 truncate text-sm font-bold text-slate-900"
                        >
                            {{
                                selectedRoute?.route_name || 'No route selected'
                            }}
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <div
                            class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600"
                        >
                            <FileText class="h-4 w-4 text-white" />
                        </div>
                        <p
                            class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                        >
                            Documents Ready
                        </p>
                        <p
                            class="mt-0.5 text-3xl font-bold text-slate-900 tabular-nums"
                        >
                            {{ uploadedDocumentsCount }}
                            <span class="text-lg font-medium text-slate-400"
                                >/ {{ requiredDocumentsCount }}</span
                            >
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <div
                            class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500"
                        >
                            <AlertCircle class="h-4 w-4 text-white" />
                        </div>
                        <p
                            class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                        >
                            Pending Docs
                        </p>
                        <p
                            class="mt-0.5 text-3xl font-bold text-slate-900 tabular-nums"
                        >
                            {{ pendingDocumentsCount }}
                        </p>
                    </div>
                </div>

                
                <form class="space-y-6" @submit.prevent="submit">
                    <div class="grid gap-6 xl:grid-cols-[1fr_320px]">
                        
                        <div class="space-y-6">
                            
                            <div
                                class="pointer-events-none rounded-xl border border-slate-200 bg-white opacity-60 shadow-sm"
                            >
                                <div
                                    class="border-b border-slate-100 px-6 py-4"
                                >
                                    <h2
                                        class="text-base font-semibold text-slate-800"
                                    >
                                        Company Information
                                    </h2>
                                    <p class="mt-0.5 text-xs text-slate-400">
                                        This vehicle is registered under your
                                        company account.
                                    </p>
                                </div>
                                <div class="grid gap-4 p-6 md:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <p
                                            class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                        >
                                            Company
                                        </p>
                                        <div
                                            class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700"
                                        >
                                            {{ company.company_name }}
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <p
                                            class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                        >
                                            Company Code
                                        </p>
                                        <div
                                            class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 font-mono text-sm font-semibold text-slate-700"
                                        >
                                            {{ company.company_code ?? '—' }}
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <p
                                            class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                        >
                                            Representative
                                        </p>
                                        <div
                                            class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700"
                                        >
                                            {{ user.name }}
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <p
                                            class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                        >
                                            Account Email
                                        </p>
                                        <div
                                            class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700"
                                        >
                                            {{ user.email }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            
                            <div
                                class="pointer-events-none rounded-xl border border-slate-200 bg-white opacity-60 shadow-sm"
                            >
                                <div
                                    class="border-b border-slate-100 px-6 py-4"
                                >
                                    <h2
                                        class="text-base font-semibold text-slate-800"
                                    >
                                        Route Assignment
                                    </h2>
                                    <p class="mt-0.5 text-xs text-slate-400">
                                        Update the operating route for this
                                        vehicle.
                                    </p>
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

                            
                            <div
                                class="pointer-events-none rounded-xl border border-slate-200 bg-white opacity-60 shadow-sm"
                            >
                                <div
                                    class="border-b border-slate-100 px-6 py-4"
                                >
                                    <h2
                                        class="text-base font-semibold text-slate-800"
                                    >
                                        Vehicle Information
                                    </h2>
                                    <p class="mt-0.5 text-xs text-slate-400">
                                        Update the primary details of the
                                        vehicle.
                                    </p>
                                </div>
                                <div class="p-6">
                                    <VehicleBasicInfoForm
                                        :form="form"
                                        :vehicle-types="vehicleTypes"
                                    />
                                </div>
                            </div>

                            
                            <div
                                class="rounded-xl border border-slate-200 bg-white shadow-sm"
                            >
                                <div
                                    class="border-b border-slate-100 px-6 py-4"
                                >
                                    <div
                                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div>
                                            <h2
                                                class="text-base font-semibold text-slate-800"
                                            >
                                                Required Documents
                                            </h2>
                                            <p
                                                class="mt-0.5 text-xs text-slate-400"
                                            >
                                                Only invalid or expired
                                                documents (including their
                                                issue/expiry dates) can be
                                                updated; all other details are
                                                locked.
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-xs font-semibold text-slate-400 tabular-nums"
                                            >
                                                {{ uploadedDocumentsCount }} /
                                                {{ requiredDocumentsCount }}
                                                ready
                                            </span>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-800"
                                                @click="scrollToSummary"
                                            >
                                                <ArrowUp class="mr-2 h-4 w-4" />
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
                                            <ArrowUp class="mr-2 h-4 w-4" />
                                            Go to Update Summary
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        <div class="space-y-4">
                            
                            <div
                                id="update-summary"
                                class="rounded-xl border border-slate-200 bg-white shadow-sm"
                            >
                                <div
                                    class="border-b border-slate-100 px-5 py-4"
                                >
                                    <h3
                                        class="text-sm font-semibold text-slate-800"
                                    >
                                        Update Summary
                                    </h3>
                                    <p class="mt-0.5 text-xs text-slate-400">
                                        Review the details before saving
                                        changes.
                                    </p>
                                </div>
                                <div class="p-5">
                                    <VehicleSummaryCard
                                        :form="form"
                                        :selected-route-name="
                                            selectedRoute?.route_name
                                        "
                                        :required-documents-count="
                                            requiredDocumentsCount
                                        "
                                        :user-name="user.name"
                                        submit-label="Save Changes"
                                    />
                                </div>
                            </div>

                            
                            <div
                                class="rounded-xl border border-slate-200 bg-white shadow-sm"
                            >
                                <div
                                    class="border-b border-slate-100 px-5 py-4"
                                >
                                    <h3
                                        class="text-sm font-semibold text-slate-800"
                                    >
                                        Editing Notes
                                    </h3>
                                    <p class="mt-0.5 text-xs text-slate-400">
                                        Keep these in mind before submitting.
                                    </p>
                                </div>
                                <div class="divide-y divide-slate-100">
                                    <div class="flex gap-3 px-5 py-4">
                                        <div
                                            class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-blue-100"
                                        >
                                            <MapPin
                                                class="h-3.5 w-3.5 text-blue-700"
                                            />
                                        </div>
                                        <div>
                                            <p
                                                class="text-xs font-semibold tracking-widest text-blue-700 uppercase"
                                            >
                                                Route Assignment
                                            </p>
                                            <p
                                                class="mt-0.5 text-xs text-slate-500"
                                            >
                                                Make sure the selected route
                                                matches the vehicle's active
                                                operating line.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex gap-3 px-5 py-4">
                                        <div
                                            class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-amber-100"
                                        >
                                            <FileText
                                                class="h-3.5 w-3.5 text-amber-700"
                                            />
                                        </div>
                                        <div>
                                            <p
                                                class="text-xs font-semibold tracking-widest text-amber-700 uppercase"
                                            >
                                                Document Updates
                                            </p>
                                            <p
                                                class="mt-0.5 text-xs text-slate-500"
                                            >
                                                Replace invalid or expired
                                                documents so your resubmission
                                                can move back to pending review.
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="flex items-center justify-between px-5 py-4"
                                    >
                                        <p
                                            class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                        >
                                            Vehicle Status
                                        </p>
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                                statusClass(vehicle.status),
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'h-1.5 w-1.5 rounded-full',
                                                    statusDot(vehicle.status),
                                                ]"
                                            />
                                            {{ humanize(vehicle.status) }}
                                        </span>
                                    </div>

                                    <div
                                        class="flex items-center justify-between px-5 py-4"
                                    >
                                        <p
                                            class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                        >
                                            Plate Number
                                        </p>
                                        <span
                                            class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold text-slate-700"
                                        >
                                            {{ vehicle.plate_number }}
                                        </span>
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
