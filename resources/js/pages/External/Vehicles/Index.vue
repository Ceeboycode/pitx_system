<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import ExternalLayout from '@/layouts/ExternalLayout.vue';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import CompanyVehicleController from '@/actions/App/Http/Controllers/CompanyVehicleController';
import {
    Building2,
    Bus,
    CheckCircle2,
    Eye,
    FileText,
    MoreHorizontal,
    Pencil,
    Plus,
    Power,
    Route as RouteIcon,
} from 'lucide-vue-next';

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

type VehicleRoute = {
    id: number;
    route_name: string;
};

type VehicleDocument = {
    id: number;
    vehicle_id: number;
    document_type: string;
    status: string;
};

type VehicleItem = {
    id: number;
    company_id: number;
    route_id?: number | null;
    vehicle_type: string;
    plate_number: string;
    body_number?: string | null;
    capacity?: number | null;
    color?: string | null;
    make_model?: string | null;
    status: string;
    created_at?: string | null;
    route?: VehicleRoute | null;
    documents?: VehicleDocument[];
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedVehicles = {
    data: VehicleItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};

const props = defineProps<{
    company: Company;
    user: User;
    vehicles: PaginatedVehicles;
    filters: { search?: string | null };
}>();

function humanize(value?: string | null) {
    if (!value) return '—';
    return value.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function formatDate(value?: string | null) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function vehicleStatusClass(status?: string | null) {
    if (status === 'active') return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    if (status === 'inactive') return 'bg-rose-100 text-rose-600 border-rose-200';
    if (status === 'suspended') return 'bg-orange-100 text-orange-700 border-orange-200';
    if (status === 'pending') return 'bg-amber-100 text-amber-700 border-amber-200';
    return 'bg-slate-100 text-slate-500 border-0';
}

function vehicleStatusDot(status?: string | null) {
    if (status === 'active') return 'bg-emerald-500';
    if (status === 'inactive') return 'bg-rose-500';
    if (status === 'suspended') return 'bg-orange-500';
    if (status === 'pending') return 'bg-amber-500';
    return 'bg-slate-400';
}

function documentStatusClass(status?: string | null) {
    if (status === 'approved') return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    if (status === 'pending') return 'bg-amber-100 text-amber-700 border-amber-200';
    if (status === 'rejected') return 'bg-rose-100 text-rose-600 border-rose-200';
    return 'bg-slate-100 text-slate-500 border-0';
}

function documentStatusDot(status?: string | null) {
    if (status === 'approved') return 'bg-emerald-500';
    if (status === 'pending') return 'bg-amber-500';
    if (status === 'rejected') return 'bg-rose-500';
    return 'bg-slate-400';
}

function documentsCount(documents?: VehicleDocument[]) {
    return documents?.length ?? 0;
}

function isSuspended(status?: string | null) {
    return status === 'suspended';
}

function canEditVehicle(vehicle: VehicleItem) {
    return !isSuspended(vehicle.status);
}

function canToggleStatus(vehicle: VehicleItem) {
    if (isSuspended(vehicle.status)) return false;
    if (!vehicle.documents?.length) return false;

    return !vehicle.documents.some(
        (doc) => doc.status === 'pending' || doc.status === 'rejected',
    );
}

function toggleLabel(status?: string | null) {
    return status === 'active' ? 'Set Inactive' : 'Set Active';
}

function toggleStatusClass(status?: string | null) {
    return status === 'active'
        ? 'text-rose-600 focus:text-rose-600 focus:bg-rose-50'
        : 'text-emerald-700 focus:text-emerald-700 focus:bg-emerald-50';
}

function vehicleActionNote(vehicle: VehicleItem) {
    if (isSuspended(vehicle.status)) {
        return 'Suspended vehicles cannot be edited or updated.';
    }

    if (!vehicle.documents?.length) {
        return 'Upload required documents first.';
    }

    if (vehicle.documents.some((doc) => doc.status === 'pending' || doc.status === 'rejected')) {
        return 'Documents must be approved before changing status.';
    }

    return '';
}

const totalVehicles = computed(() => props.vehicles.total ?? 0);

const activeVehicles = computed(() =>
    props.vehicles.data.filter((v) => v.status === 'active').length,
);

const statusDialog = reactive({
    open: false,
    vehicle: null as VehicleItem | null,
});

const statusConfirmOpen = computed({
    get: () => statusDialog.open,
    set: (value: boolean) => {
        statusDialog.open = value;
    },
});

function openStatusDialog(vehicle: VehicleItem) {
    if (!canToggleStatus(vehicle)) return;

    statusDialog.vehicle = vehicle;
    statusDialog.open = true;
}

function confirmToggleStatus() {
    if (!statusDialog.vehicle) return;

    router.patch(
        CompanyVehicleController.toggleStatus(statusDialog.vehicle.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                statusDialog.open = false;
                statusDialog.vehicle = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Registered Vehicles" />

    <ExternalLayout :company="company" :user="user">
        <div class="min-h-screen bg-slate-50/60">
            <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-slate-400">
                            <Building2 class="h-3.5 w-3.5" />
                            {{ company.company_code ?? company.company_name }}
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                            Registered Vehicles
                        </h1>
                        <p class="text-sm text-slate-500">
                            View and manage your registered vehicles.
                        </p>
                    </div>

                    <Button
                        as-child
                        class="shrink-0 self-start gap-2 rounded-lg border-0 bg-blue-700 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
                    >
                        <Link :href="CompanyVehicleController.create().url">
                            <Plus class="h-4 w-4" />
                            Register Vehicle
                        </Link>
                    </Button>
                </div>

                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-blue-700">
                            <Bus class="h-4 w-4 text-white" />
                        </div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                            Total Vehicles
                        </p>
                        <p class="mt-0.5 text-3xl font-bold tabular-nums text-slate-900">
                            {{ totalVehicles }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600">
                            <CheckCircle2 class="h-4 w-4 text-white" />
                        </div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                            Active
                        </p>
                        <p class="mt-0.5 text-3xl font-bold tabular-nums text-slate-900">
                            {{ activeVehicles }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-sky-600">
                            <RouteIcon class="h-4 w-4 text-white" />
                        </div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                            With Route
                        </p>
                        <p class="mt-0.5 text-3xl font-bold tabular-nums text-slate-900">
                            {{ vehicles.data.filter((v) => v.route).length }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-600">
                            <Building2 class="h-4 w-4 text-white" />
                        </div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                            Company
                        </p>
                        <p class="mt-0.5 truncate text-sm font-bold text-slate-900">
                            {{ company.company_code ?? company.company_name }}
                        </p>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-slate-800">Vehicle List</h2>
                            <p class="mt-0.5 text-xs text-slate-400">
                                Search by plate number, vehicle type, body number, or model.
                            </p>
                        </div>

                        <div class="sm:w-72">
                            <SearchInput
                                :route="CompanyVehicleController.index().url"
                                :initial-value="filters.search"
                                placeholder="Search vehicles…"
                                :only="['vehicles', 'filters']"
                            />
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="border-slate-100 bg-slate-50/70 hover:bg-slate-50/70">
                                    <TableHead class="pl-5 text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Plate Number
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Vehicle Info
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Route
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Documents
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Status
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Created
                                    </TableHead>
                                    <TableHead class="pr-5 text-right text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow v-if="vehicles.data.length === 0" class="hover:bg-transparent">
                                    <TableCell colspan="7" class="py-20 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100">
                                                <Bus class="h-6 w-6 text-slate-400" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-600">
                                                    No vehicles found
                                                </p>
                                                <p class="mt-0.5 text-xs text-slate-400">
                                                    Try adjusting your search.
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="vehicle in vehicles.data"
                                    :key="vehicle.id"
                                    class="border-slate-100 transition-colors hover:bg-slate-50/80"
                                >
                                    <TableCell class="pl-5">
                                        <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold tracking-wide text-slate-700">
                                            {{ vehicle.plate_number }}
                                        </span>
                                    </TableCell>

                                    <TableCell>
                                        <div class="flex items-start gap-2.5">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100">
                                                <Bus class="h-3.5 w-3.5 text-blue-700" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-800">
                                                    {{ vehicle.vehicle_type }}
                                                </p>
                                                <p class="text-xs text-slate-400">
                                                    Body: {{ vehicle.body_number || '—' }}
                                                </p>
                                                <p class="text-xs text-slate-400">
                                                    {{ vehicle.make_model || '—' }}
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <div v-if="vehicle.route" class="flex items-start gap-1.5">
                                            <RouteIcon class="mt-0.5 h-3.5 w-3.5 shrink-0 text-sky-600" />
                                            <div>
                                                <p class="text-sm font-medium text-slate-700">
                                                    {{ vehicle.route.route_name }}
                                                </p>
                                                <p class="text-xs text-slate-400">
                                                    Cap: {{ vehicle.capacity ?? '—' }}
                                                </p>
                                            </div>
                                        </div>

                                        <div v-else class="flex items-center gap-1.5">
                                            <RouteIcon class="h-3.5 w-3.5 text-slate-300" />
                                            <span class="text-xs text-slate-400">No route assigned</span>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <Popover v-if="vehicle.documents?.length">
                                            <PopoverTrigger as-child>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    class="h-7 gap-1.5 rounded-lg border-slate-200 text-xs text-slate-600 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                                >
                                                    <FileText class="h-3 w-3" />
                                                    {{ documentsCount(vehicle.documents) }}
                                                    doc{{ documentsCount(vehicle.documents) !== 1 ? 's' : '' }}
                                                </Button>
                                            </PopoverTrigger>

                                            <PopoverContent class="w-80 rounded-xl border-slate-200 p-0 shadow-lg">
                                                <div class="border-b border-slate-100 px-4 py-3">
                                                    <h4 class="text-sm font-semibold text-slate-800">
                                                        Document Statuses
                                                    </h4>
                                                    <p class="mt-0.5 text-xs text-slate-400">
                                                        Documents attached to this vehicle.
                                                    </p>
                                                </div>

                                                <div class="divide-y divide-slate-100 p-2">
                                                    <div
                                                        v-for="doc in vehicle.documents"
                                                        :key="doc.id"
                                                        class="flex items-center justify-between gap-3 rounded-lg px-2 py-2"
                                                    >
                                                        <p class="truncate text-xs font-medium text-slate-700">
                                                            {{ humanize(doc.document_type) }}
                                                        </p>

                                                        <span
                                                            :class="[
                                                                'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-medium',
                                                                documentStatusClass(doc.status),
                                                            ]"
                                                        >
                                                            <span
                                                                :class="[
                                                                    'h-1.5 w-1.5 rounded-full',
                                                                    documentStatusDot(doc.status),
                                                                ]"
                                                            />
                                                            {{ humanize(doc.status) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </PopoverContent>
                                        </Popover>

                                        <div v-else class="flex items-center gap-1.5">
                                            <FileText class="h-3.5 w-3.5 text-slate-300" />
                                            <span class="text-xs text-slate-400">No documents</span>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                                vehicleStatusClass(vehicle.status),
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'h-1.5 w-1.5 rounded-full',
                                                    vehicleStatusDot(vehicle.status),
                                                ]"
                                            />
                                            {{ humanize(vehicle.status) }}
                                        </span>
                                    </TableCell>

                                    <TableCell class="text-sm text-slate-400">
                                        {{ formatDate(vehicle.created_at) }}
                                    </TableCell>

                                    <TableCell class="pr-5 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-8 w-8 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                                                >
                                                    <MoreHorizontal class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent
                                                align="end"
                                                class="w-56 rounded-xl border-slate-200 shadow-lg"
                                            >
                                                <DropdownMenuLabel
                                                    class="text-xs font-semibold uppercase tracking-widest text-slate-400"
                                                >
                                                    Actions
                                                </DropdownMenuLabel>

                                                <DropdownMenuSeparator class="bg-slate-100" />

                                                <DropdownMenuItem
                                                    as-child
                                                    class="rounded-lg text-slate-700 focus:bg-blue-50 focus:text-blue-700"
                                                >
                                                    <Link :href="CompanyVehicleController.show(vehicle.id).url">
                                                        <Eye class="mr-2 h-4 w-4" />
                                                        View Details
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="canEditVehicle(vehicle)"
                                                    as-child
                                                    class="rounded-lg text-slate-700 focus:bg-amber-50 focus:text-amber-700"
                                                >
                                                    <Link :href="CompanyVehicleController.edit(vehicle.id).url">
                                                        <Pencil class="mr-2 h-4 w-4" />
                                                        Edit Vehicle
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-else
                                                    disabled
                                                    class="rounded-lg text-slate-300"
                                                >
                                                    <Pencil class="mr-2 h-4 w-4" />
                                                    Edit Vehicle
                                                </DropdownMenuItem>

                                                <DropdownMenuSeparator class="bg-slate-100" />

                                                <DropdownMenuItem
                                                    v-if="canToggleStatus(vehicle)"
                                                    :class="['rounded-lg', toggleStatusClass(vehicle.status)]"
                                                    @click="openStatusDialog(vehicle)"
                                                >
                                                    <Power class="mr-2 h-4 w-4" />
                                                    {{ toggleLabel(vehicle.status) }}
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-else
                                                    disabled
                                                    class="rounded-lg text-slate-300"
                                                >
                                                    <Power class="mr-2 h-4 w-4" />
                                                    {{ toggleLabel(vehicle.status) }}
                                                </DropdownMenuItem>

                                                <div
                                                    v-if="!canEditVehicle(vehicle) || !canToggleStatus(vehicle)"
                                                    class="px-2 pb-2 pt-1 text-left text-[11px] text-slate-400"
                                                >
                                                    {{ vehicleActionNote(vehicle) }}
                                                </div>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div v-if="vehicles.last_page > 1" class="border-t border-slate-100 px-5 py-3">
                        <InertiaPagination
                            :links="vehicles.links"
                            :meta="{
                                from: vehicles.from,
                                to: vehicles.to,
                                total: vehicles.total,
                            }"
                        />
                    </div>
                </div>
            </div>
        </div>

        <AlertDialog v-model:open="statusConfirmOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{ statusDialog.vehicle ? toggleLabel(statusDialog.vehicle.status) : 'Update Status' }}
                    </AlertDialogTitle>

                    <AlertDialogDescription>
                        This will update the status of
                        <span class="font-semibold text-slate-800">
                            {{ statusDialog.vehicle?.plate_number || 'the selected vehicle' }}
                        </span>.
                        Are you sure you want to continue?
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg" @click="statusDialog.vehicle = null">
                        Cancel
                    </AlertDialogCancel>

                    <AlertDialogAction
                        class="rounded-lg border-0 bg-blue-700 text-white hover:bg-blue-800"
                        @click="confirmToggleStatus"
                    >
                        Confirm
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </ExternalLayout>
</template>
