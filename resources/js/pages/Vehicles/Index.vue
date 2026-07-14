<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import ImportVehicleDialog from '@/components/vehicle/ImportVehicleDialog.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';

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
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    RiArchive2Line,
    RiArrowDownSLine,
    RiArrowUpDownLine,
    RiArrowUpSLine,
    RiDownloadLine,
    RiFileSearchLine,
    RiFileTextLine,
    RiFilter2Line,
    RiLoaderLine,
    RiMore2Line,
    RiShutDownLine,
    RiUploadLine,
} from 'vue-remix-icons';
import { computed, ref } from 'vue';

import { destroy, index, show, trash } from '@/routes/vehicles';
import { type BreadcrumbItem } from '@/types';

/* ── Types ──────────────────────────────────────────────────────── */

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

/* ── Props ───────────────────────────────────────────────────────── */

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

/* ── Breadcrumbs ─────────────────────────────────────────────────── */

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vehicles', href: index().url },
];

/* ── Import / Export ─────────────────────────────────────────────── */

const importOpen = ref(false);
const exporting = ref(false);

function triggerExport() {
    exporting.value = true;
    const a = document.createElement('a');
    a.href = '/vehicles/export';
    a.style.display = 'none';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(() => {
        exporting.value = false;
    }, 2000);
}

function onImportDone() {
    router.reload({ only: ['vehicles'] });
}

/* ── Dialog state ────────────────────────────────────────────────── */

const archiveDialogOpen = ref(false);
const selectedVehicle = ref<VehicleItem | null>(null);
const suspendDialogOpen = ref(false);
const activateDialogOpen = ref(false);
const statusVehicle = ref<VehicleItem | null>(null);
const suspendRemarks = ref('');

/* ── Filter & Sort state ─────────────────────────────────────────── */

const statusFilter = ref<string>(props.filters.status ?? 'all');
const vehicleTypeFilter = ref<string>(props.filters.vehicle_type ?? 'all');
const routeFilter = ref<string>(props.filters.route_id ?? 'all');
const sortBy = ref<SortField>(props.filters.sort_by ?? null);
const sortDir = ref<SortDir>(props.filters.sort_dir ?? 'asc');
const filterOpen = ref(false);

const hasActiveFilters = computed(
    () =>
        (statusFilter.value && statusFilter.value !== 'all') ||
        (vehicleTypeFilter.value && vehicleTypeFilter.value !== 'all') ||
        (routeFilter.value && routeFilter.value !== 'all') ||
        sortBy.value !== null,
);

const hasCategoryFilters = computed(
    () =>
        (statusFilter.value && statusFilter.value !== 'all') ||
        (vehicleTypeFilter.value && vehicleTypeFilter.value !== 'all') ||
        (routeFilter.value && routeFilter.value !== 'all'),
);

const activeFilterCount = computed(() => {
    let count = 0;
    if (statusFilter.value && statusFilter.value !== 'all') count++;
    if (vehicleTypeFilter.value && vehicleTypeFilter.value !== 'all') count++;
    if (routeFilter.value && routeFilter.value !== 'all') count++;
    return count;
});

function applyFilters(
    overrides: Record<string, string | null | undefined> = {},
) {
    router.get(
        index().url,
        {
            search: props.filters.search ?? undefined,
            status:
                statusFilter.value !== 'all' ? statusFilter.value : undefined,
            vehicle_type:
                vehicleTypeFilter.value !== 'all'
                    ? vehicleTypeFilter.value
                    : undefined,
            route_id:
                routeFilter.value !== 'all' ? routeFilter.value : undefined,
            sort_by: sortBy.value ?? undefined,
            sort_dir: sortBy.value ? sortDir.value : undefined,
            ...overrides,
        },
        {
            preserveState: true,
            replace: true,
            only: ['vehicles', 'filters', 'flash'],
        },
    );

    filterOpen.value = false;
}

function onStatusChange(val: string) {
    statusFilter.value = val;
}

function onVehicleTypeChange(val: string) {
    vehicleTypeFilter.value = val;
}

function onRouteChange(val: string) {
    routeFilter.value = val;
}

function toggleSort(field: SortField) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDir.value = 'asc';
    }
    applyFilters();
}

function clearFilters() {
    statusFilter.value = 'all';
    vehicleTypeFilter.value = 'all';
    routeFilter.value = 'all';
    sortBy.value = null;
    sortDir.value = 'asc';
    applyFilters({
        status: undefined,
        vehicle_type: undefined,
        route_id: undefined,
        sort_by: undefined,
        sort_dir: undefined,
    });
}

/* ── Sort icon helpers ───────────────────────────────────────────── */

function sortIcon(field: SortField) {
    if (sortBy.value !== field) return RiArrowUpDownLine;
    return sortDir.value === 'asc' ? RiArrowUpSLine : RiArrowDownSLine;
}

function sortIconClass(field: SortField) {
    return sortBy.value === field
        ? 'text-custom-primary'
        : 'text-custom-shadow/40';
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

const humanize = (text?: string | null) => {
    if (!text) return '—';
    return text.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
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
        case 'invalid':
        case 'inactive':
        case 'needs_revision':
            return 'bg-rose-500';
        default:
            return 'bg-slate-400';
    }
}

function toggleStatusClass(status?: string | null): string {
    return status === 'active'
        ? 'text-rose-600 focus:bg-rose-50 focus:text-rose-600'
        : 'text-emerald-700 focus:bg-emerald-50 focus:text-emerald-700';
}

const toggleLabel = (status?: string | null) =>
    status === 'active'
        ? 'Suspend'
        : status === 'suspended'
          ? 'Unsuspend'
          : 'Suspend';

const canToggle = (vehicle: VehicleItem) =>
    !['pending', 'for_verification'].includes(vehicle.status ?? '');

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
            onSuccess: () => {
                suspendDialogOpen.value = false;
                statusVehicle.value = null;
                suspendRemarks.value = '';
            },
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
            onSuccess: () => {
                activateDialogOpen.value = false;
                statusVehicle.value = null;
            },
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
</script>

<template>
    <Head title="Vehicles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4">
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row gap-2">
                    <div class="flex flex-col">
                        <CardTitle class="flex items-center gap-2">
                            <span class="font-semibold">Vehicles</span>
                        </CardTitle>
                        <CardDescription>List of all vehicles in the system.</CardDescription>
                    </div>
                    <div class="flex flex-1 justify-end gap-2">
                        <div class="lg:flex items-center gap-2 sm:justify-end">
                            <Button
                                variant="float-primary"
                                class="hidden lg:flex"
                                @click="importOpen = true"
                            >
                                <RiUploadLine class="h-4 w-4 shrink-0" />
                                <span>Import</span>
                            </Button>

                            <Button
                                variant="float"
                                class="hidden lg:flex"
                                :disabled="exporting"
                                @click="triggerExport"
                            >
                                <RiLoaderLine
                                    v-if="exporting"
                                    class="h-4 w-4 shrink-0 animate-spin"
                                />
                                <RiDownloadLine
                                    v-else
                                    class="h-4 w-4 shrink-0"
                                />
                                <span>{{ exporting ? 'Exporting...' : 'Export' }}</span>
                            </Button>

                            <DropdownMenu class="w-fit">
                                <DropdownMenuTrigger as-child class="m-0">
                                    <div class="inline-flex">
                                        <Button
                                            variant="header-actions"
                                            class="text-custom-shadow"
                                            size="icon"
                                            aria-label="Open vehicle actions"
                                        >
                                            <RiMore2Line class="h-4 w-4 shrink-0" />
                                        </Button>
                                    </div>
                                </DropdownMenuTrigger>

                                <DropdownMenuContent align="end" class="w-fit">
                                    <DropdownMenuItem
                                        as-child
                                        class="cursor-pointer lg:hidden"
                                        @click="importOpen = true"
                                    >
                                        <button type="button" class="flex w-full items-center">
                                            <RiUploadLine class="h-4 w-4" />
                                            Import
                                        </button>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        as-child
                                        class="cursor-pointer lg:hidden"
                                        @click="triggerExport"
                                    >
                                        <button type="button" class="flex w-full items-center">
                                            <RiDownloadLine class="h-4 w-4" />
                                            Export
                                        </button>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem as-child class="cursor-pointer">
                                        <Link :href="trash().url" class="flex items-center">
                                            <RiArchive2Line class="h-4 w-4" />
                                            Archives
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                    <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                        <div class="w-full">
                            <SearchInput
                                :route="index().url"
                                :initial-value="filters.search"
                                placeholder="Search vehicles…"
                                :only="['vehicles', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>

                        <div class="flex w-fit flex-row gap-2 lg:items-center lg:justify-between">
                            <Popover v-model:open="filterOpen">
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="header-actions"
                                        size="icon-text"
                                        class="rounded-full"
                                        :class="
                                            activeFilterCount > 0
                                                ? 'bg-custom-secondary/20 hover:bg-custom-secondary/80 hover:text-custom-bg-light transition-all duration-300 dark:hover:text-custom-shadow'
                                                : ''
                                        "
                                    >
                                        <RiFilter2Line class="h-3.5 w-3.5" />
                                        <span class="hidden lg:flex">
                                            {{
                                                activeFilterCount > 0
                                                    ? (activeFilterCount === 1 ? '1 filter active' : `${activeFilterCount} filters active`)
                                                    : 'Filter'
                                            }}
                                        </span>
                                    </Button>
                                </PopoverTrigger>

                                <PopoverContent align="start">
                                    <div class="grid gap-y-2">
                                            <div class="space-y-2">
                                                <p class="text-sm text-custom-shadow/80">
                                                    Status
                                                </p>
                                                <Select
                                                    :model-value="statusFilter"
                                                    @update:model-value="
                                                        onStatusChange
                                                    "
                                                >
                                                    <SelectTrigger
                                                        class="w-full"
                                                    >
                                                        <SelectValue
                                                            placeholder="All Statuses"
                                                            class="flex justify-start"
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent
                                                    >
                                                        <SelectItem
                                                            value="all"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            All Statuses
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="active"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            Active
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="suspended"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            Suspended
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="for_verification"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            For Verification
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="pending"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            Pending
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="needs_revision"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            Needs Revision
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div class="space-y-2">
                                                <p class="text-sm text-custom-shadow/80">
                                                    Vehicle Type
                                                </p>
                                                <Select
                                                    :model-value="
                                                        vehicleTypeFilter
                                                    "
                                                    @update:model-value="
                                                        onVehicleTypeChange
                                                    "
                                                >
                                                    <SelectTrigger
                                                        class="w-full"
                                                    >
                                                        <SelectValue
                                                            placeholder="All Types"
                                                            class="flex justify-start"
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent
                                                    >
                                                        <SelectItem
                                                            value="all"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            All Types
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="bus"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            Bus
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="minibus"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            Minibus
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="jeepney"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            Jeepney
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="van"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            Van
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="uv_express"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            UV Express
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div class="space-y-2">
                                                <p class="text-sm text-custom-shadow/80">
                                                    Route
                                                </p>
                                                <Select
                                                    :model-value="routeFilter"
                                                    @update:model-value="
                                                        onRouteChange
                                                    "
                                                >
                                                    <SelectTrigger
                                                        class="w-full"
                                                    >
                                                        <SelectValue
                                                            placeholder="All Routes"
                                                            class="flex justify-start"
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent
                                                    >
                                                        <SelectItem
                                                            value="all"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            All Routes
                                                        </SelectItem>
                                                        <SelectItem
                                                            v-for="route in props.routes"
                                                            :key="route.id"
                                                            :value="
                                                                String(route.id)
                                                            "
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            {{
                                                                route.route_name
                                                            }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <hr class="my-1 h-px border-0 bg-custom-bg-dark">

                                            <div class="flex w-full flex-row items-center justify-between">
                                                <Button
                                                    v-if="hasActiveFilters"
                                                    size="sm"
                                                    variant="destructive"
                                                    @click="clearFilters"
                                                >
                                                    Clear
                                                </Button>

                                                <div class="ml-auto flex items-center gap-2">
                                                    <Button
                                                        variant="ghost-outline"
                                                        size="sm"
                                                        @click="filterOpen = false"
                                                    >
                                                        Cancel
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="float-primary"
                                                        @click="applyFilters"
                                                    >
                                                        Apply
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    </PopoverContent>
                                </Popover>
                        </div>
                    </div>

                    <Card
                        :class="[
                            'flex min-h-0 flex-1 max-h-fit flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none',
                            vehicles.data.length === 0 ? 'border-dashed' : 'border-solid',
                        ]"
                    >
                        <div v-if="vehicles.data.length > 0" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                            <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                                <div class="grid grid-cols-8 gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                    <div class="col-span-1 flex h-10 items-center justify-start px-0 pl-3 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Company</div>
                                    <div class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Route</div>
                                    <div class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Vehicle</div>
                                    <div class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Plate</div>

                                    <button
                                        type="button"
                                        class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('capacity')"
                                    >
                                        Cap.
                                        <component
                                            :is="sortIcon('capacity')"
                                            class="h-3.5 w-3.5"
                                            :class="sortIconClass('capacity')"
                                        />
                                    </button>

                                    <button
                                        type="button"
                                        class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('status')"
                                    >
                                        Status
                                        <component
                                            :is="sortIcon('status')"
                                            class="h-3.5 w-3.5"
                                            :class="sortIconClass('status')"
                                        />
                                    </button>

                                    <div class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Remarks</div>
                                    <div class="col-span-1 flex h-10 items-center justify-end px-0 pr-3 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Actions</div>
                                </div>
                            </div>

                            <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                <div
                                    v-for="(vehicle, rowIndex) in vehicles.data"
                                    :key="vehicle.id"
                                    :class="[
                                        'grid grid-cols-8 items-center border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light',
                                        rowIndex === vehicles.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                    ]"
                                >
                                    <div class="col-span-1 flex min-w-0 justify-start py-1.5 pl-3 text-sm font-medium">
                                        <span class="truncate">{{ vehicle.company?.company_name || '—' }}</span>
                                    </div>

                                    <div class="col-span-1 flex min-w-0 justify-start py-1.5 text-sm">
                                        <span class="truncate">{{ vehicle.route?.route_name || '—' }}</span>
                                    </div>

                                    <div class="col-span-1 flex min-w-0 flex-col justify-center py-1.5">
                                        <p class="truncate text-sm font-medium">{{ humanize(vehicle.vehicle_type) }}</p>
                                        <p class="truncate text-xs text-custom-shadow/70">{{ vehicle.body_number || '—' }}</p>
                                    </div>

                                    <div class="col-span-1 flex justify-start py-1.5">
                                        <span class="rounded bg-custom-bg px-2 py-0.5 font-mono text-xs font-semibold text-custom-shadow dark:bg-custom-bg-light">
                                            {{ vehicle.plate_number || '—' }}
                                        </span>
                                    </div>

                                    <div class="col-span-1 flex justify-start py-1.5 text-sm tabular-nums text-custom-shadow/80">
                                        {{ vehicle.capacity || '—' }}
                                    </div>

                                    <div class="col-span-1 flex justify-start py-1.5">
                                        <Badge :class="['gap-1.5', statusClass(vehicle.status)]">
                                            <span :class="['h-1.5 w-1.5 rounded-full', statusDot(vehicle.status)]" />
                                            {{ humanize(vehicle.status) }}
                                        </Badge>
                                    </div>

                                    <div class="col-span-1 flex justify-start py-1.5">
                                        <Popover v-if="vehicle.remarks">
                                            <PopoverTrigger as-child>
                                                <Button
                                                    variant="ghost-outline"
                                                    size="sm"
                                                    class="h-7 px-2 text-xs"
                                                >
                                                    <RiFileTextLine class="h-3.5 w-3.5" />
                                                    View
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent class="w-64 p-3 text-sm text-custom-shadow">
                                                {{ vehicle.remarks }}
                                            </PopoverContent>
                                        </Popover>
                                        <span v-else class="text-xs text-custom-shadow/70">—</span>
                                    </div>

                                    <div class="col-span-1 flex justify-end py-1.5 pr-3 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="table-more"
                                                    size="icon-more"
                                                >
                                                    <RiMore2Line class="h-4 w-4" />
                                                    <span class="sr-only">Open actions</span>
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end" class="w-fit rounded-lg shadow-lg">
                                                <DropdownMenuLabel class="text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">
                                                    {{ vehicle.plate_number || 'Vehicle' }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem as-child class="cursor-pointer rounded-lg">
                                                    <Link
                                                        :href="show({ vehicle: vehicle.id }).url"
                                                        class="flex items-center"
                                                    >
                                                        <RiFileSearchLine class="h-4 w-4" />
                                                        Review
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="vehicle.status === 'active' || vehicle.status === 'inactive'"
                                                    :disabled="!canToggle(vehicle)"
                                                    :class="[
                                                        'cursor-pointer rounded-lg',
                                                        canToggle(vehicle) ? toggleStatusClass(vehicle.status) : 'text-slate-300',
                                                    ]"
                                                    @click="canToggle(vehicle) && openSuspendDialog(vehicle)"
                                                >
                                                    <RiShutDownLine class="h-4 w-4" />
                                                    Suspend
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-else-if="vehicle.status === 'suspended'"
                                                    :disabled="!canToggle(vehicle)"
                                                    :class="[
                                                        'cursor-pointer rounded-lg',
                                                        canToggle(vehicle) ? toggleStatusClass(vehicle.status) : 'text-slate-300',
                                                    ]"
                                                    @click="canToggle(vehicle) && openActivateDialog(vehicle)"
                                                >
                                                    <RiShutDownLine class="h-4 w-4" />
                                                    Unsuspend
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-else
                                                    disabled
                                                    class="rounded-lg text-slate-300"
                                                >
                                                    <RiShutDownLine class="h-4 w-4" />
                                                    {{ toggleLabel(vehicle.status) }}
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                            <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                                <img
                                    :src="emptyRafikiUrl"
                                    alt=""
                                    class="w-1/3 object-contain opacity-90"
                                    aria-hidden="true"
                                />
                                <div class="space-y-1">
                                    <p class="text-custom-shadow text-base font-semibold">No vehicles found</p>
                                    <p class="text-custom-shadow/80 text-sm">
                                        {{ hasActiveFilters ? 'Try adjusting or clearing your filters.' : 'Try adjusting your search.' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </Card>

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

        <!-- Archive dialog -->
        <AlertDialog v-model:open="archiveDialogOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>Archive Vehicle</AlertDialogTitle>
                    <AlertDialogDescription>
                        You are about to archive
                        <span class="font-semibold text-foreground">{{
                            selectedVehicle?.plate_number || 'this vehicle'
                        }}</span
                        >. You can restore it later from Archived Vehicles.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel
                        class="rounded-lg"
                        @click="selectedVehicle = null"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction
                        class="rounded-lg border-0 bg-rose-600 text-white hover:bg-rose-700"
                        @click="
                            selectedVehicle && archiveVehicle(selectedVehicle)
                        "
                    >
                        Archive
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Suspend dialog (requires remarks) -->
        <AlertDialog v-model:open="suspendDialogOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle
                        >Set Vehicle to Suspended</AlertDialogTitle
                    >
                    <AlertDialogDescription>
                        Provide a reason before suspending
                        <span class="font-semibold text-foreground">{{
                            statusVehicle?.plate_number || 'this vehicle'
                        }}</span
                        >.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-slate-600"
                        >Reason</label
                    >
                    <textarea
                        v-model="suspendRemarks"
                        rows="3"
                        class="w-full rounded-lg border border-slate-200 bg-white p-2 text-sm focus:border-blue-500 focus:outline-none"
                        placeholder="Brief reason (required)"
                    />
                </div>
                <AlertDialogFooter>
                    <AlertDialogCancel
                        class="rounded-lg"
                        @click="statusVehicle = null"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction
                        class="rounded-lg border-0 bg-rose-600 text-white hover:bg-rose-700"
                        :disabled="!suspendRemarks.trim()"
                        @click="confirmSuspend"
                    >
                        Suspend
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Activate dialog -->
        <AlertDialog v-model:open="activateDialogOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>Activate Vehicle</AlertDialogTitle>
                    <AlertDialogDescription>
                        Activate
                        <span class="font-semibold text-foreground">{{
                            statusVehicle?.plate_number || 'this vehicle'
                        }}</span
                        >?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel
                        class="rounded-lg"
                        @click="statusVehicle = null"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction
                        class="rounded-lg border-0 bg-blue-700 text-white hover:bg-blue-800"
                        @click="confirmActivate"
                    >
                        Confirm
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Import dialog -->
        <ImportVehicleDialog v-model:open="importOpen" @done="onImportDone" />
    </AppLayout>
</template>
