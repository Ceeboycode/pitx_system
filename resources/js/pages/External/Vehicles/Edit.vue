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
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

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

const selectedRoute = computed(() => {
    return (
        props.routes.find(
            (route) => String(route.id) === String(form.route_id),
        ) ?? null
    );
});

const requiredDocumentsCount = computed(
    () => Object.keys(props.docTypes).length,
);

function submit() {
    form.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(`/company/vehicles/${props.vehicle.id}`, {
        forceFormData: true,
        onFinish: () => {
            form.transform((data) => data);
        },
    });
}

function setDocumentFile(index: number, event: Event) {
    const input = event.target as HTMLInputElement;
    form.documents[index].file = input.files?.[0] ?? null;
}
</script>

<template>
    <Head :title="`Edit Vehicle ${vehicle.plate_number}`" />

    <ExternalLayout :company="company" :user="user">
        <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
            <div
                class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <Badge variant="secondary">Vehicles</Badge>
                        <Badge variant="outline">Edit</Badge>
                    </div>

                    <h1 class="text-2xl font-semibold tracking-tight">
                        Edit Vehicle
                    </h1>

                    <p class="text-sm text-muted-foreground">
                        Update vehicle details and route assignment.
                    </p>
                </div>

                <div class="flex gap-2">
                    <Button as-child variant="outline">
                        <Link
                            :href="
                                CompanyVehicleController.show(vehicle.id).url
                            "
                        >
                            View Vehicle
                        </Link>
                    </Button>

                    <Button as-child variant="outline">
                        <Link :href="CompanyVehicleController.index().url">
                            Back to Vehicles
                        </Link>
                    </Button>
                </div>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <div class="grid gap-6 xl:grid-cols-[1fr_320px]">
                    <div class="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Company Information</CardTitle>
                                <CardDescription>
                                    This vehicle is registered under your
                                    company account.
                                </CardDescription>
                            </CardHeader>

                            <CardContent class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <p class="text-sm font-medium">Company</p>
                                    <p
                                        class="rounded-md border px-3 py-2 text-sm"
                                    >
                                        {{ company.company_name }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm font-medium">
                                        Company Code
                                    </p>
                                    <p
                                        class="rounded-md border px-3 py-2 text-sm"
                                    >
                                        {{ company.company_code ?? '—' }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm font-medium">
                                        Representative
                                    </p>
                                    <p
                                        class="rounded-md border px-3 py-2 text-sm"
                                    >
                                        {{ user.name }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm font-medium">
                                        Account Email
                                    </p>
                                    <p
                                        class="rounded-md border px-3 py-2 text-sm"
                                    >
                                        {{ user.email }}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Route Assignment</CardTitle>
                                <CardDescription>
                                    Update the operating route for this vehicle.
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
                                    Update the primary details of the vehicle.
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
                                    Reupload is allowed only when a document is
                                    pending or rejected.
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
                                <CardTitle>Update Summary</CardTitle>
                                <CardDescription>
                                    Review the details before saving changes.
                                </CardDescription>
                            </CardHeader>

                            <CardContent>
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
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </form>
        </div>
    </ExternalLayout>
</template>
