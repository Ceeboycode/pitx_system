<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import { can } from '@/lib/can';

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
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Archive,
    Bus,
    ChevronRight,
    Download,
    FileSearch,
    MoreHorizontal,
    Route as RouteIcon,
    ShieldCheck,
    ShieldOff,
    Upload,
    X,
    FileText,
} from 'lucide-vue-next';
import { ref } from 'vue';

import { destroy, index, show, trash } from '@/routes/vehicles';
import { type BreadcrumbItem } from '@/types';

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
    route?: { route_name?: string | null } | null;
};

const props = defineProps<{
    vehicles: {
        data: VehicleItem[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { search: string | null };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vehicles', href: index().url },
];

const archiveDialogOpen = ref(false);
const selectedVehicle   = ref<VehicleItem | null>(null);
const statusDialogOpen  = ref(false);
const statusVehicle     = ref<VehicleItem | null>(null);

/* ── Filter & Sort state ─────────────────────────────────────────── */

const statusFilter      = ref<string>(props.filters.status       ?? 'all');
const vehicleTypeFilter = ref<string>(props.filters.vehicle_type ?? 'all');
const routeFilter       = ref<string>(props.filters.route_id     ?? 'all');
const sortBy            = ref<SortField>(props.filters.sort_by   ?? null);
const sortDir           = ref<SortDir>(props.filters.sort_dir    ?? 'asc');

const hasActiveFilters = computed(() =>
    (statusFilter.value && statusFilter.value !== 'all') ||
    (vehicleTypeFilter.value && vehicleTypeFilter.value !== 'all') ||
    (routeFilter.value && routeFilter.value !== 'all') ||
    sortBy.value !== null,
);

function applyFilters(overrides: Record<string, string | null | undefined> = {}) {
    router.get(
        index().url,
        {
            search:       props.filters.search ?? undefined,
            status:       statusFilter.value !== 'all'      ? statusFilter.value      : undefined,
            vehicle_type: vehicleTypeFilter.value !== 'all' ? vehicleTypeFilter.value : undefined,
            route_id:     routeFilter.value !== 'all'       ? routeFilter.value       : undefined,
            sort_by:      sortBy.value ?? undefined,
            sort_dir:     sortBy.value ? sortDir.value : undefined,
            ...overrides,
        },
        { preserveState: true, replace: true, only: ['vehicles', 'filters', 'flash'] },
    );
}

function onStatusChange(val: string) {
    statusFilter.value = val;
    applyFilters();
}

function onVehicleTypeChange(val: string) {
    vehicleTypeFilter.value = val;
    applyFilters();
}

function onRouteChange(val: string) {
    routeFilter.value = val;
    applyFilters();
}

function toggleSort(field: SortField) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value  = field;
        sortDir.value = 'asc';
    }
    applyFilters();
}

function clearFilters() {
    statusFilter.value      = 'all';
    vehicleTypeFilter.value = 'all';
    routeFilter.value       = 'all';
    sortBy.value            = null;
    sortDir.value           = 'asc';
    applyFilters({
        status: undefined, vehicle_type: undefined,
        route_id: undefined, sort_by: undefined, sort_dir: undefined,
    });
}

/* ── Sort icon helpers ───────────────────────────────────────────── */

function sortIcon(field: SortField) {
    if (sortBy.value !== field) return ArrowUpDown;
    return sortDir.value === 'asc' ? ArrowUp : ArrowDown;
}

function sortIconClass(field: SortField) {
    return sortBy.value === field ? 'text-blue-600' : 'text-muted-foreground/40';
}

/* ── Helpers ─────────────────────────────────────────────────────── */

const formatDate = (value?: string | null) => {
    if (!value) return '—';

    return new Date(value).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

function statusClass(status?: string | null): string {
    switch (status) {
        case 'active':
        case 'verified':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'for_verification':
            return 'bg-violet-100 text-violet-700 border-violet-200';
        case 'draft':
        case 'pending':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'suspended':
            return 'bg-orange-100 text-orange-700 border-orange-200';
        case 'invalid':
        case 'inactive':
        case 'needs_revision':
            return 'bg-rose-100 text-rose-600 border-rose-200';
        default:
            return 'bg-slate-100 text-slate-500 border-0';
    }
}

function statusDot(status?: string | null): string {
    switch (status) {
        case 'active':
        case 'verified':
            return 'bg-emerald-500';
        case 'for_verification':
            return 'bg-violet-500';
        case 'draft':
        case 'pending':
            return 'bg-amber-500';
        case 'suspended':
            return 'bg-orange-500';
        case 'invalid':
        case 'inactive':
        case 'needs_revision':
            return 'bg-rose-500';
        default:
            return 'bg-slate-400';
    }
}

function toggleStatusClass(status?: string | null): string {
    return status === 'suspended'
        ? 'text-emerald-700 focus:bg-emerald-50 focus:text-emerald-700'
        : 'text-orange-600 focus:bg-orange-50 focus:text-orange-600';
}

const humanize = (text?: string | null) => {
    if (!text) return '—';

    return text.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
};

const toggleLabel = (status?: string | null) =>
    status === 'active' ? 'Set Inactive' : 'Set Active';

/* ── Actions ─────────────────────────────────────────────────────── */

const openArchiveDialog = (vehicle: VehicleItem) => {
    selectedVehicle.value = vehicle;
    archiveDialogOpen.value = true;
};

const openSuspendDialog = (vehicle: VehicleItem) => {
    statusVehicle.value = vehicle;
    suspendRemarks.value = '';
    suspendDialogOpen.value = true;
};

const openActivateDialog = (vehicle: VehicleItem) => {
    statusVehicle.value = vehicle;
    activateDialogOpen.value = true;
};

const confirmSuspend = () => {
    if (!statusVehicle.value) return;
    router.patch(
        `/vehicles/${statusVehicle.value.id}/toggle-status`,
        { remarks: suspendRemarks.value },
        {
            preserveScroll: true,
            onSuccess: () => { suspendDialogOpen.value = false; statusVehicle.value = null; suspendRemarks.value = ''; },
        },
    );
};

const confirmActivate = () => {
    if (!statusVehicle.value) return;

    router.patch(
        `/vehicles/${statusVehicle.value.id}/toggle-status`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => { statusDialogOpen.value = false; statusVehicle.value = null; },
        },
    );
};

const archiveVehicle = (vehicle: VehicleItem) => {
    router.delete(destroy({ vehicle: vehicle.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            archiveDialogOpen.value = false;
            selectedVehicle.value = null;
        },
    });
};

const canViewVehicle = can('vehicles.view');
const canViewArchived = can('vehicles.viewAny');
const canDeleteVehicle = can('vehicles.delete');
const canToggleVehicleStatus = can('vehicles.toggleStatus');
const canImportVehicle = can('vehicles.create');
const canExportVehicle = can('vehicles.viewAny');
</script>

<template>
    <Head title="Vehicles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-5">
                <CardHeader>
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            Vehicles
                        </CardTitle>
                        <CardDescription class="mt-1">
                            List of all vehicles in the system.
                        </CardDescription>
                    </div>

                    <CardAction class="flex items-center gap-2">
                        <Button
                            as-child
                            size="sm"
                            variant="outline"
                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                        >
                            <Link :href="trash().url">
                                <Archive class="mr-2 h-4 w-4" />
                                View Archived
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="index().url"
                                :initial-value="filters.search"
                                placeholder="Search vehicles…"
                                :only="['vehicles', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>

                        <div class="flex gap-2 sm:justify-end">
                            <Button
                                v-if="canImportVehicle"
                                size="sm"
                                variant="outline"
                                class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                            >
                                <Upload class="mr-2 h-4 w-4" />
                                Import
                            </Button>

                            <Button
                                v-if="canExportVehicle"
                                size="sm"
                                variant="outline"
                                class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                            >
                                <Download class="mr-2 h-4 w-4" />
                                Export
                            </Button>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 hover:bg-muted/40">
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Company</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Route</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Vehicle Info</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Plate Number</TableHead>

                                    <!-- Sortable: Capacity -->
                                    <TableHead
                                        class="cursor-pointer select-none text-[11px] font-bold uppercase tracking-widest text-muted-foreground hover:text-foreground"
                                        @click="toggleSort('capacity')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Cap.
                                            <component :is="sortIcon('capacity')" class="h-3.5 w-3.5" :class="sortIconClass('capacity')" />
                                        </div>
                                    </TableHead>

                                    <!-- Sortable: Status -->
                                    <TableHead
                                        class="cursor-pointer select-none text-[11px] font-bold uppercase tracking-widest text-muted-foreground hover:text-foreground"
                                        @click="toggleSort('status')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Status
                                            <component :is="sortIcon('status')" class="h-3.5 w-3.5" :class="sortIconClass('status')" />
                                        </div>
                                    </TableHead>

                                    <!-- Sortable: Created -->
                                    <TableHead
                                        class="cursor-pointer select-none text-[11px] font-bold uppercase tracking-widest text-muted-foreground hover:text-foreground"
                                        @click="toggleSort('created_at')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Created
                                            <component :is="sortIcon('created_at')" class="h-3.5 w-3.5" :class="sortIconClass('created_at')" />
                                        </div>
                                    </TableHead>

                                    <TableHead class="text-right text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Actions</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow v-if="vehicles.data.length === 0" class="hover:bg-transparent">
                                    <TableCell colspan="8" class="py-20 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                                                <Bus class="h-6 w-6 text-muted-foreground/40" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-foreground">No vehicles found</p>
                                                <p class="mt-0.5 text-xs text-muted-foreground">Try adjusting your search.</p>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="vehicle in vehicles.data"
                                    :key="vehicle.id"
                                    class="transition-colors hover:bg-muted/30"
                                >
                                    <TableCell class="text-sm font-medium">
                                        {{ vehicle.company?.company_name || '—' }}
                                    </TableCell>

                                    <TableCell>
                                        <div v-if="vehicle.route?.route_name" class="flex items-center gap-1.5">
                                            <RouteIcon class="h-3.5 w-3.5 shrink-0 text-sky-600" />
                                            <span class="text-sm">{{ vehicle.route.route_name }}</span>
                                        </div>
                                        <span v-else class="text-sm text-muted-foreground">—</span>
                                    </TableCell>

                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-blue-100">
                                                <Bus class="h-3.5 w-3.5 text-blue-700" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium">{{ humanize(vehicle.vehicle_type) }}</p>
                                                <p class="text-xs text-muted-foreground">{{ vehicle.body_number || '—' }}</p>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <span class="rounded bg-muted px-2 py-0.5 font-mono text-xs font-semibold">
                                            {{ vehicle.plate_number || '—' }}
                                        </span>
                                    </TableCell>

                                    <TableCell class="text-sm text-muted-foreground tabular-nums">
                                        {{ vehicle.capacity || '—' }}
                                    </TableCell>

                                    <TableCell>
                                        <Badge :class="['gap-1.5', statusClass(vehicle.status)]">
                                            <span :class="['h-1.5 w-1.5 rounded-full', statusDot(vehicle.status)]" />
                                            {{ humanize(vehicle.status) }}
                                        </Badge>
                                    </TableCell>

                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ formatDate(vehicle.created_at) }}
                                    </TableCell>

                                    <!-- Remarks -->
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Popover v-if="vehicle.remarks">
                                                <PopoverTrigger as-child>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        class="h-7 rounded-lg border-slate-200 text-xs text-slate-600 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                                    >
                                                        <FileText class="mr-1.5 h-3.5 w-3.5" />
                                                        View
                                                    </Button>
                                                </PopoverTrigger>
                                                <PopoverContent class="w-64 rounded-xl border-slate-200 p-3 text-sm text-slate-700 shadow-lg">
                                                    {{ vehicle.remarks }}
                                                </PopoverContent>
                                            </Popover>
                                            <Badge v-if="vehicle.remarks" variant="secondary" class="rounded-full bg-slate-100 text-[11px] font-semibold text-slate-700">
                                                Has remarks
                                            </Badge>
                                            <span v-else class="text-xs text-muted-foreground">&mdash;</span>
                                        </div>
                                    </TableCell>

                                    <TableCell class="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-8 w-8 rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground"
                                                >
                                                    <MoreHorizontal class="h-4 w-4" />
                                                    <span class="sr-only">Open actions</span>
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end" class="w-52 rounded-xl border-slate-200 shadow-lg">
                                                <DropdownMenuLabel class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                                    {{ vehicle.plate_number || 'Vehicle' }}
                                                </DropdownMenuLabel>

                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    v-if="canViewVehicle"
                                                    as-child
                                                    class="rounded-lg text-blue-700 focus:bg-blue-50 focus:text-blue-700"
                                                >
                                                    <Link :href="show({ vehicle: vehicle.id }).url" class="flex items-center">
                                                        <FileSearch class="mr-2 h-4 w-4" />
                                                        Review
                                                        <ChevronRight class="ml-auto h-3.5 w-3.5 text-blue-400" />
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    as-child
                                                    class="rounded-lg text-amber-600 focus:bg-amber-50 focus:text-amber-700"
                                                >
                                                    <Link :href="edit({ vehicle: vehicle.id }).url">
                                                        <Pencil class="mr-2 h-4 w-4" />
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    :class="['rounded-lg', toggleStatusClass(vehicle.status)]"
                                                    @click="openStatusDialog(vehicle)"
                                                >
                                                    <ShieldCheck v-if="vehicle.status === 'suspended'" class="mr-2 h-4 w-4" />
                                                    <ShieldOff v-else class="mr-2 h-4 w-4" />
                                                    {{ toggleLabel(vehicle.status) }}
                                                </DropdownMenuItem>

                                                <DropdownMenuSeparator v-if="canDeleteVehicle" />

                                                <DropdownMenuItem
                                                    v-if="canDeleteVehicle"
                                                    class="rounded-lg text-rose-600 focus:bg-rose-50 focus:text-rose-600"
                                                    @click="openArchiveDialog(vehicle)"
                                                >
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

                    <InertiaPagination
                        :links="vehicles.links"
                        :meta="{
                            from: vehicles.from,
                            to: vehicles.to,
                            total: vehicles.total,
                        }"
                    />
                </CardContent>
            </Card>
        </div>

        <AlertDialog v-model:open="archiveDialogOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>Archive Vehicle</AlertDialogTitle>
                    <AlertDialogDescription>
                        You are about to archive
                        <span class="font-semibold text-foreground">{{ selectedVehicle?.plate_number || 'this vehicle' }}</span>.
                        You can restore it later from Archived Vehicles.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg" @click="selectedVehicle = null">Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        class="rounded-lg bg-rose-600 text-white hover:bg-rose-700 border-0"
                        @click="selectedVehicle && archiveVehicle(selectedVehicle)"
                    >
                        Archive
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Status dialog -->
        <AlertDialog v-model:open="statusDialogOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>Activate Vehicle</AlertDialogTitle>
                    <AlertDialogDescription>
                        Update status for
                        <span class="font-semibold text-foreground">{{ statusVehicle?.plate_number || 'this vehicle' }}</span>?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg" @click="statusVehicle = null">Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        class="rounded-lg bg-blue-700 text-white hover:bg-blue-800 border-0"
                        @click="confirmToggleStatus"
                    >
                        {{ statusVehicle ? toggleLabel(statusVehicle.status) : 'Confirm' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
