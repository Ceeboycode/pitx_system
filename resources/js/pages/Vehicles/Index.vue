<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Archive,
    ArrowUpDown,
    Bus,
    FileSearch,
    MoreHorizontal,
    Pencil,
    Power,
} from 'lucide-vue-next';
import { ref } from 'vue';

import { destroy, edit, index, show, trash } from '@/routes/vehicles';
import { type BreadcrumbItem } from '@/types';

type SortField = 'capacity' | 'created_at' | 'status' | null;
type SortDir = 'asc' | 'desc';

type VehicleItem = {
    id: number;
    status?: string | null;
    vehicle_type?: string | null;
    plate_number?: string | null;
    body_number?: string | null;
    capacity?: string | number | null;
    created_at?: string | null;
    remarks?: string | null;
    company?: { company_name?: string | null } | null;
    route?: { id?: number; route_name?: string | null } | null;
};

const props = defineProps<{
    vehicles: {
        data: VehicleItem[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: {
        search: string | null;
        status: string | null;
        vehicle_type: string | null;
        route_id: string | null;
        sort_by: SortField;
        sort_dir: SortDir;
    };
    routes: { id: number; route_name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Vehicles', href: index().url }];

const statusFilter = ref(props.filters.status ?? 'all');
const vehicleTypeFilter = ref(props.filters.vehicle_type ?? 'all');
const routeFilter = ref(props.filters.route_id ?? 'all');
const sortBy = ref<SortField>(props.filters.sort_by ?? null);
const sortDir = ref<SortDir>(props.filters.sort_dir ?? 'asc');

const archiveDialogOpen = ref(false);
const suspendDialogOpen = ref(false);
const activateDialogOpen = ref(false);
const selectedVehicle = ref<VehicleItem | null>(null);
const suspendRemarks = ref('');

function applyFilters() {
    router.get(
        index().url,
        {
            search: props.filters.search ?? undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
            vehicle_type: vehicleTypeFilter.value !== 'all' ? vehicleTypeFilter.value : undefined,
            route_id: routeFilter.value !== 'all' ? routeFilter.value : undefined,
            sort_by: sortBy.value ?? undefined,
            sort_dir: sortBy.value ? sortDir.value : undefined,
        },
        { preserveState: true, replace: true, only: ['vehicles', 'filters', 'flash'] },
    );
}

function toggleSort(field: Exclude<SortField, null>) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDir.value = 'asc';
    }
    applyFilters();
}

function openArchiveDialog(vehicle: VehicleItem) {
    selectedVehicle.value = vehicle;
    archiveDialogOpen.value = true;
}

function openStatusDialog(vehicle: VehicleItem) {
    selectedVehicle.value = vehicle;
    suspendRemarks.value = '';
    if (vehicle.status === 'suspended') {
        activateDialogOpen.value = true;
    } else {
        suspendDialogOpen.value = true;
    }
}

function archiveVehicle() {
    if (!selectedVehicle.value) return;
    router.delete(destroy({ vehicle: selectedVehicle.value.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            archiveDialogOpen.value = false;
            selectedVehicle.value = null;
        },
    });
}

function confirmSuspend() {
    if (!selectedVehicle.value) return;
    router.patch(
        `/vehicles/${selectedVehicle.value.id}/toggle-status`,
        { remarks: suspendRemarks.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                suspendDialogOpen.value = false;
                selectedVehicle.value = null;
                suspendRemarks.value = '';
            },
        },
    );
}

function confirmActivate() {
    if (!selectedVehicle.value) return;
    router.patch(
        `/vehicles/${selectedVehicle.value.id}/toggle-status`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                activateDialogOpen.value = false;
                selectedVehicle.value = null;
            },
        },
    );
}

function humanize(value?: string | null) {
    if (!value) return '-';
    return value.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function formatDate(value?: string | null) {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function statusClass(status?: string | null) {
    switch (status) {
        case 'active':
        case 'verified':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'for_verification':
            return 'bg-violet-100 text-violet-700 border-violet-200';
        case 'draft':
        case 'pending':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'invalid':
        case 'inactive':
        case 'needs_revision':
        case 'suspended':
            return 'bg-rose-100 text-rose-600 border-rose-200';
        default:
            return 'bg-slate-100 text-slate-500 border-0';
    }
}
</script>

<template>
    <Head title="Vehicles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-5">
                <CardHeader class="gap-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Bus class="h-5 w-5 text-blue-700" />
                                Vehicles
                            </CardTitle>
                            <CardDescription class="mt-1">
                                Review and manage registered vehicles.
                            </CardDescription>
                        </div>

                        <Button as-child size="sm" variant="outline">
                            <Link :href="trash().url">
                                <Archive class="mr-2 h-4 w-4" />
                                View Archived
                            </Link>
                        </Button>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="index().url"
                                :initial-value="filters.search"
                                placeholder="Search vehicles..."
                                :only="['vehicles', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>

                        <Select :model-value="statusFilter" @update:model-value="(value) => { statusFilter = value; applyFilters(); }">
                            <SelectTrigger class="w-40">
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Statuses</SelectItem>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="suspended">Suspended</SelectItem>
                                <SelectItem value="for_verification">For Verification</SelectItem>
                                <SelectItem value="pending">Pending</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select :model-value="vehicleTypeFilter" @update:model-value="(value) => { vehicleTypeFilter = value; applyFilters(); }">
                            <SelectTrigger class="w-40">
                                <SelectValue placeholder="Vehicle Type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Types</SelectItem>
                                <SelectItem value="bus">Bus</SelectItem>
                                <SelectItem value="minibus">Minibus</SelectItem>
                                <SelectItem value="jeepney">Jeepney</SelectItem>
                                <SelectItem value="van">Van</SelectItem>
                                <SelectItem value="uv_express">UV Express</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select :model-value="routeFilter" @update:model-value="(value) => { routeFilter = value; applyFilters(); }">
                            <SelectTrigger class="w-44">
                                <SelectValue placeholder="Route" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Routes</SelectItem>
                                <SelectItem v-for="route in props.routes" :key="route.id" :value="String(route.id)">
                                    {{ route.route_name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardHeader>

                <CardContent>
                    <div class="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Company</TableHead>
                                    <TableHead>Route</TableHead>
                                    <TableHead>Vehicle</TableHead>
                                    <TableHead>Plate</TableHead>
                                    <TableHead class="cursor-pointer" @click="toggleSort('capacity')">Capacity <ArrowUpDown class="ml-1 inline h-3.5 w-3.5" /></TableHead>
                                    <TableHead class="cursor-pointer" @click="toggleSort('status')">Status <ArrowUpDown class="ml-1 inline h-3.5 w-3.5" /></TableHead>
                                    <TableHead class="cursor-pointer" @click="toggleSort('created_at')">Created <ArrowUpDown class="ml-1 inline h-3.5 w-3.5" /></TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="vehicles.data.length === 0">
                                    <TableCell colspan="8" class="py-12 text-center text-sm text-muted-foreground">
                                        No vehicles found.
                                    </TableCell>
                                </TableRow>

                                <TableRow v-for="vehicle in vehicles.data" :key="vehicle.id">
                                    <TableCell>{{ vehicle.company?.company_name || '-' }}</TableCell>
                                    <TableCell>{{ vehicle.route?.route_name || '-' }}</TableCell>
                                    <TableCell>
                                        <div>
                                            <div class="font-medium">{{ humanize(vehicle.vehicle_type) }}</div>
                                            <div class="text-xs text-muted-foreground">{{ vehicle.body_number || '-' }}</div>
                                        </div>
                                    </TableCell>
                                    <TableCell>{{ vehicle.plate_number || '-' }}</TableCell>
                                    <TableCell>{{ vehicle.capacity || '-' }}</TableCell>
                                    <TableCell>
                                        <Badge :class="statusClass(vehicle.status)">
                                            {{ humanize(vehicle.status) }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{{ formatDate(vehicle.created_at) }}</TableCell>
                                    <TableCell class="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon">
                                                    <MoreHorizontal class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem as-child>
                                                    <Link :href="show({ vehicle: vehicle.id }).url">
                                                        <FileSearch class="mr-2 h-4 w-4" />
                                                        Review
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem as-child>
                                                    <Link :href="edit({ vehicle: vehicle.id }).url">
                                                        <Pencil class="mr-2 h-4 w-4" />
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem @click="openStatusDialog(vehicle)">
                                                    <Power class="mr-2 h-4 w-4" />
                                                    {{ vehicle.status === 'suspended' ? 'Unsuspend' : 'Suspend' }}
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem class="text-rose-600 focus:text-rose-600" @click="openArchiveDialog(vehicle)">
                                                    <Archive class="mr-2 h-4 w-4" />
                                                    Archive
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div class="mt-4">
                        <InertiaPagination
                            :links="vehicles.links"
                            :meta="{ from: vehicles.from, to: vehicles.to, total: vehicles.total }"
                        />
                    </div>
                </CardContent>
            </Card>
        </div>

        <AlertDialog v-model:open="archiveDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Archive Vehicle</AlertDialogTitle>
                    <AlertDialogDescription>
                        Archive {{ selectedVehicle?.plate_number || 'this vehicle' }}?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction class="bg-rose-600 text-white hover:bg-rose-700" @click="archiveVehicle">
                        Archive
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog v-model:open="suspendDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Suspend Vehicle</AlertDialogTitle>
                    <AlertDialogDescription>
                        Add a reason before suspending {{ selectedVehicle?.plate_number || 'this vehicle' }}.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <textarea
                    v-model="suspendRemarks"
                    rows="3"
                    class="w-full rounded-lg border border-slate-200 bg-white p-2 text-sm"
                    placeholder="Reason"
                />
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction :disabled="!suspendRemarks.trim()" class="bg-rose-600 text-white hover:bg-rose-700" @click="confirmSuspend">
                        Suspend
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog v-model:open="activateDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Activate Vehicle</AlertDialogTitle>
                    <AlertDialogDescription>
                        Activate {{ selectedVehicle?.plate_number || 'this vehicle' }}?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction class="bg-blue-700 text-white hover:bg-blue-800" @click="confirmActivate">
                        Confirm
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
