<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import ImportVehicleDialog from '@/components/vehicle/ImportVehicleDialog.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';

import {
    Table,
    TableColumn,
    // TableSortColumn,
    TableHeader,
    TableContent,
    TableRow,
    TableCard,
    TableData,
    TableMoreButton,
} from '@/components/ui/_table';
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
import { VehicleStatusDialog } from '@/components/internal/vehicles';

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    RiArchive2Line,
    RiArrowDownSLine,
    RiArrowUpDownLine,
    RiArrowUpSLine,
    RiFileAddLine,
    RiFileCheckLine,
    RiFileUploadLine,
    RiFileSearchLine,
    RiFileTextLine,
    RiFilter2Line,
    RiLoaderLine,
    RiMore2Line,
    RiOctagonLine,
    RiCloseLine,
    RiBusLine,
    RiShutDownLine,
    RiSpam2Line,
} from 'vue-remix-icons';
import { computed, ref } from 'vue';
import type { AcceptableValue } from 'reka-ui';
import { PanelLayout } from '@/components/ui/_panels';
import { destroy, index, show, trash } from '@/routes/vehicles';
import { type BreadcrumbItem } from '@/types';
import {
    operationalStatusClass,
    operationalStatusDot,
    operationalStatusLabel,
    verificationStatusClass,
    verificationStatusDot,
    verificationStatusLabel,
    humanize,
} from '@/lib/vehicle-status';



type SortField = 'capacity' | 'created_at' | 'status' | null;
type SortDir = 'asc' | 'desc';

type VehicleItem = {
    id: number;
    status?: string | null;
    verification_status?: string | null;
    vehicle_type?: { id: number; type_name: string } | null;
    plate_number: string | null;
    body_number?: string | null;
    capacity?: string | number | null;
    created_at?: string | null;
    operator_remark?: string | null;
    suspension_remark?: string | null;
    verification_remark?: string | null;
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
    vehicleTypes: { id: number; type_name: string }[];
}>();



const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vehicles', href: index().url },
];



const importOpen = ref(false);
const exporting = ref(false);
const previewedVehicle = ref<VehicleItem | null>(null);

function openPreview(vehicle: VehicleItem) {
    previewedVehicle.value = vehicle;
}

// const openMenus = ref<Record<number, { open: boolean; x: number; y: number }>>({})

// function handleRowContextMenu(event: MouseEvent, vehicle: VehicleItem) {
//     event.preventDefault()

//     openMenus.value[vehicle.id] = {
//         open: true,
//         x: event.clientX,
//         y: event.clientY,
//     }
// }

const openMenus = ref<Record<number, {
  open: boolean
  x: number
  y: number
  mode: 'trigger' | 'context'
}>>({})

function setMenuState(vehicleId: number, next: { open: boolean; x: number; y: number; mode: 'trigger' | 'context' }) {
    openMenus.value = {
        ...openMenus.value,
        [vehicleId]: next,
    }
}

function openRowMenu(event: MouseEvent, vehicle: VehicleItem) {
    event.preventDefault()

    setMenuState(vehicle.id, {
        open: true,
        x: event.clientX,
        y: event.clientY,
        mode: 'context',
    })
}

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


const archiveDialogOpen = ref(false);
const selectedVehicle = ref<VehicleItem | null>(null);
const statusDialogOpen = ref(false);
const statusVehicle = ref<VehicleItem | null>(null);
const targetStatus = ref<'active' | 'inactive' | 'suspended'>('suspended');



const statusFilter = ref<string>(props.filters.status ?? 'all');
const vehicleTypeFilter = ref<string | number>(props.filters.vehicle_type ?? 'all');
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

function onStatusChange(val: AcceptableValue) {
    statusFilter.value = String(val ?? 'all');
}

function onVehicleTypeChange(val: AcceptableValue) {
    vehicleTypeFilter.value = typeof val === 'string' || typeof val === 'number' ? val : 'all';
}

function onRouteChange(val: AcceptableValue) {
    routeFilter.value = String(val ?? 'all');
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



function sortIcon(field: SortField) {
    if (sortBy.value !== field) return RiArrowUpDownLine;
    return sortDir.value === 'asc' ? RiArrowUpSLine : RiArrowDownSLine;
}

function sortIconClass(field: SortField) {
    return sortBy.value === field
        ? 'text-custom-primary'
        : 'text-custom-shadow/40';
}





const canToggle = (_vehicle: VehicleItem) => true;

const openArchiveDialog = (vehicle: VehicleItem) => {
    selectedVehicle.value = vehicle;
    archiveDialogOpen.value = true;
};

const openStatusDialog = (
    vehicle: VehicleItem,
    status: 'active' | 'inactive' | 'suspended',
) => {
    statusVehicle.value = vehicle;
    targetStatus.value = status;
    statusDialogOpen.value = true;
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
        <PanelLayout>
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
                                        class="group cursor-pointer"
                                        @click="importOpen = true"
                                    >
                                        <RiFileAddLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                        Import
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        class="group cursor-pointer"
                                        :disabled="exporting"
                                        @click="triggerExport"
                                    >
                                        <RiLoaderLine v-if="exporting" class="h-4 w-4 animate-spin text-custom-shadow" />
                                        <RiFileUploadLine v-else class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                        {{ exporting ? 'Exporting...' : 'Export' }}
                                    </DropdownMenuItem>
                                    <DropdownMenuItem as-child class="group cursor-pointer">
                                        <Link :href="trash().url" class="flex items-center">
                                            <RiArchive2Line class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow transition-all duration-200" />
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
                                placeholder="Search vehicles..."
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
                                                ? 'bg-custom-secondary/20 hover:bg-custom-secondary/80 hover:text-custom-bg-light transition-all duration-200 dark:hover:text-custom-shadow'
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
                                            <div class="flex flex-col gap-y-1">
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
                                                            value="inactive"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            Inactive
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="suspended"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            Suspended
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div class="flex flex-col gap-y-1">
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
                                                    <SelectContent>
                                                        <SelectItem
                                                            value="all"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            All Types
                                                        </SelectItem>
                                                        <SelectItem v-for="vehicleType in vehicleTypes" :key="vehicleType.id" :value="vehicleType.id" class="cursor-pointer text-sm">
                                                            {{ vehicleType.type_name }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div class="flex flex-col gap-y-1">
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

                    <TableCard :table-data-length="vehicles.data.length">
                        <!-- my attempt at making this into a custom component -->
                        <template v-if="vehicles.data.length > 0">
                            <TableHeader class="grid-cols-10">
                                <TableColumn class="pl-3">
                                    Company
                                </TableColumn>
                                <TableColumn>
                                    Route
                                </TableColumn>
                                <TableColumn>
                                    Vehicle
                                </TableColumn>
                                <TableColumn>
                                    Plate
                                </TableColumn>

                                <!-- _TableSortColumn -->
                                <!-- TODO: make this component work -->
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

                                <TableColumn>
                                    Verification
                                </TableColumn>

                                <TableColumn>
                                    Operator Remark
                                </TableColumn>
                                <TableColumn>
                                    Admin Remark
                                </TableColumn>
                            </TableHeader>

                            <TableContent>
                                <TableRow
                                    v-for="(vehicle, rowIndex) in vehicles.data"
                                    :key="vehicle.id"
                                    :class="[
                                        'grid-cols-10',
                                        rowIndex === vehicles.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                        // make these class things its own attribute for table row:))
                                        previewedVehicle?.id === vehicle.id ? 'bg-custom-secondary/10' : '',
                                    ]"
                                    :status="vehicle.status === 'inactive' ? 'inactive' : 'default'"
                                    @click.left="openPreview(vehicle)"
                                    @contextmenu.prevent="openRowMenu($event, vehicle)"
                                >
                                    <TableData class="pl-3 font-semibold">
                                        <span class="truncate">{{ vehicle.company?.company_name || '—' }}</span>
                                    </TableData>
                                    
                                    <TableData>
                                        <span class="truncate">{{ vehicle.route?.route_name || '—' }}</span>
                                    </TableData>

                                    <TableData class="justify-center flex-col">
                                        <p class="truncate text-sm font-medium">{{ vehicle.vehicle_type?.type_name ?? '—' }}</p>
                                        <p class="truncate text-xs text-muted-foreground">{{ vehicle.body_number || '—' }}</p>
                                    </TableData>

                                    <TableData>
                                        <span class="rounded bg-custom-bg px-2 py-0.5 font-mono text-xs font-semibold dark:bg-custom-bg-light">
                                            {{ vehicle.plate_number || '—' }}
                                        </span>
                                    </TableData>

                                    <TableData>
                                        <span class="tabular-nums">
                                            {{ vehicle.capacity || '—' }}
                                        </span>
                                    </TableData>

                                    <TableData>
                                        <Badge :class="['gap-1.5', operationalStatusClass(vehicle.status)]">
                                            <span :class="['h-1.5 w-1.5 rounded-full', operationalStatusDot(vehicle.status)]" />
                                            {{ operationalStatusLabel(vehicle.status) }}
                                        </Badge>
                                    </TableData>

                                    <TableData>
                                        <Badge :class="['gap-1.5', verificationStatusClass(vehicle.verification_status)]">
                                            <span :class="['h-1.5 w-1.5 rounded-full', verificationStatusDot(vehicle.verification_status)]" />
                                            {{ verificationStatusLabel(vehicle.verification_status) }}
                                        </Badge>
                                    </TableData>

                                    <TableData>
                                        <Popover v-if="vehicle.operator_remark">
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
                                            <PopoverContent class="w-64 p-3 text-sm">
                                                {{ vehicle.operator_remark }}
                                            </PopoverContent>
                                        </Popover>
                                        <span v-else class="text-xs text-custom-shadow/70">—</span>
                                    </TableData>

                                    <TableData>
                                        <Popover v-if="vehicle.suspension_remark">
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
                                            <PopoverContent class="w-64 p-3 text-sm">
                                                {{ vehicle.suspension_remark }}
                                            </PopoverContent>
                                        </Popover>
                                        <span v-else class="text-xs text-custom-shadow/70">—</span>
                                    </TableData>

                                    <TableMoreButton
                                        :open="openMenus[vehicle.id]?.open ?? false"
                                        :x="openMenus[vehicle.id]?.x ?? 0"
                                        :y="openMenus[vehicle.id]?.y ?? 0"
                                        :mode="openMenus[vehicle.id]?.mode ?? 'trigger'"
                                        @update:open="(value) => {
                                            const current = openMenus[vehicle.id] ?? { open: false, x: 0, y: 0, mode: 'trigger' }

                                            setMenuState(vehicle.id, {
                                                ...current,
                                                open: value,
                                                mode: value ? current.mode : 'trigger',
                                            })
                                        }"
                                    >
                                        <DropdownMenuLabel>
                                            <span>{{ vehicle.plate_number }}</span>
                                        </DropdownMenuLabel>
                                        <DropdownMenuItem as-child class="group cursor-pointer">
                                            <Link
                                                :href="show({ vehicle: vehicle.id }).url"
                                                class="flex items-center"
                                            >
                                                <RiFileCheckLine class="h-4 w-4 shrink-0 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                Review
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="vehicle.status !== 'active'"
                                            :disabled="!canToggle(vehicle)"
                                            class="group cursor-pointer rounded-md"
                                            @click="canToggle(vehicle) && openStatusDialog(vehicle, 'active')"
                                        >
                                            <RiOctagonLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                            <span class="text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg">Set Active</span>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="vehicle.status !== 'inactive'"
                                            :disabled="!canToggle(vehicle)"
                                            class="group cursor-pointer rounded-md"
                                            @click="canToggle(vehicle) && openStatusDialog(vehicle, 'inactive')"
                                        >
                                            <RiShutDownLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                            <span class="text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg">Set Inactive</span>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="vehicle.status !== 'suspended'"
                                            :disabled="!canToggle(vehicle)"
                                            class="group cursor-pointer rounded-md"
                                            @click="canToggle(vehicle) && openStatusDialog(vehicle, 'suspended')"
                                        >
                                            <RiSpam2Line class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                            <span class="text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg">Suspend</span>
                                        </DropdownMenuItem>
                                    </TableMoreButton>

                                    <!-- <div class="col-span-1 flex justify-end py-1.5 pr-3 text-right" @click.stop>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="table-more"
                                                    size="icon-more"
                                                >
                                                    <RiMore2Line class="h-4 w-4" />

                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end" class="">
                                                <DropdownMenuLabel>
                                                    {{ vehicle.plate_number || 'Vehicle' }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuItem as-child class="group cursor-pointer rounded-md">
                                                    <Link
                                                        :href="show({ vehicle: vehicle.id }).url"
                                                        class="flex items-center"
                                                    >
                                                        <RiFileCheckLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                        Review
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="vehicle.status !== 'active'"
                                                    :disabled="!canToggle(vehicle)"
                                                    class="group cursor-pointer rounded-md"
                                                    @click="canToggle(vehicle) && openStatusDialog(vehicle, 'active')"
                                                >
                                                    <RiOctagonLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                    <span class="text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg">Set Active</span>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="vehicle.status !== 'inactive'"
                                                    :disabled="!canToggle(vehicle)"
                                                    class="group cursor-pointer rounded-md"
                                                    @click="canToggle(vehicle) && openStatusDialog(vehicle, 'inactive')"
                                                >
                                                    <RiShutDownLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                    <span class="text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg">Set Inactive</span>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="vehicle.status !== 'suspended'"
                                                    :disabled="!canToggle(vehicle)"
                                                    class="group cursor-pointer rounded-md"
                                                    @click="canToggle(vehicle) && openStatusDialog(vehicle, 'suspended')"
                                                >
                                                    <RiSpam2Line class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                    <span class="text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg">Suspend</span>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div> -->
                                </TableRow>
                            </TableContent>
                        </template>

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
                    </TableCard>

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

            <Card class="hidden min-h-0 lg:flex lg:h-full lg:w-100">
                <CardHeader v-if="previewedVehicle" class="flex flex-row items-start justify-between gap-3">
                    <div class="min-w-0">
                        <CardTitle class="truncate uppercase">{{ previewedVehicle.plate_number || 'Vehicle' }}</CardTitle>
                        <CardDescription>Preview</CardDescription>
                    </div>
                    <Button variant="header-actions" size="icon" class="h-8 w-8 shrink-0 rounded-full" @click="previewedVehicle = null">
                        <RiCloseLine class="h-4 w-4" />
                    </Button>
                </CardHeader>

                <CardContent v-if="previewedVehicle" class="no-scrollbar min-h-0 flex-1 space-y-2 overflow-y-auto py-2">
                    <div class="flex aspect-4/3 items-center justify-center rounded-md border border-dashed border-custom-bg-dark bg-custom-bg text-custom-shadow/70 dark:border-none dark:bg-custom-bg-dark">
                        <RiBusLine class="h-16 w-16" />
                    </div>
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Operational Status</span>
                            <Badge :class="['gap-1.5', operationalStatusClass(previewedVehicle.status)]"><span :class="['h-1.5 w-1.5 rounded-full', operationalStatusDot(previewedVehicle.status)]" />{{ operationalStatusLabel(previewedVehicle.status) }}</Badge>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Verification Status</span>
                            <Badge :class="['gap-1.5', verificationStatusClass(previewedVehicle.verification_status)]"><span :class="['h-1.5 w-1.5 rounded-full', verificationStatusDot(previewedVehicle.verification_status)]" />{{ verificationStatusLabel(previewedVehicle.verification_status) }}</Badge>
                        </div>
                        <div class="flex items-start justify-between gap-3"><span class="text-sm font-semibold text-custom-shadow">Company</span><span class="text-right text-sm">{{ previewedVehicle.company?.company_name || 'Not assigned' }}</span></div>
                        <div class="flex items-start justify-between gap-3"><span class="text-sm font-semibold text-custom-shadow">Route</span><span class="text-right text-sm">{{ previewedVehicle.route?.route_name || 'Not assigned' }}</span></div>
                        <div class="flex items-start justify-between gap-3"><span class="text-sm font-semibold text-custom-shadow">Vehicle Type</span><span class="text-right text-sm">{{ previewedVehicle.vehicle_type?.type_name ?? '—' }}</span></div>
                        <div class="flex items-start justify-between gap-3"><span class="text-sm font-semibold text-custom-shadow">Body Number</span><span class="text-right text-sm">{{ previewedVehicle.body_number || 'Not recorded' }}</span></div>
                        <div class="flex items-start justify-between gap-3"><span class="text-sm font-semibold text-custom-shadow">Capacity</span><span class="text-right text-sm">{{ previewedVehicle.capacity || 'Not recorded' }}</span></div>
                        <div v-if="previewedVehicle.operator_remark" class="space-y-1"><span class="text-sm font-semibold text-custom-shadow">Operator Remark</span><p class="rounded-md bg-custom-bg p-3 text-sm text-custom-shadow/80 dark:bg-custom-bg-dark">{{ previewedVehicle.operator_remark }}</p></div>
                        <div v-if="previewedVehicle.suspension_remark" class="space-y-1"><span class="text-sm font-semibold text-custom-shadow">Admin Remark</span><p class="rounded-md bg-custom-bg p-3 text-sm text-custom-shadow/80 dark:bg-custom-bg-dark">{{ previewedVehicle.suspension_remark }}</p></div>
                        <div v-if="previewedVehicle.verification_remark" class="space-y-1"><span class="text-sm font-semibold text-custom-shadow">Verification Remark</span><p class="rounded-md bg-custom-bg p-3 text-sm text-custom-shadow/80 dark:bg-custom-bg-dark">{{ previewedVehicle.verification_remark }}</p></div>
                    </div>
                    <hr class="my-4 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap gap-2">
                            <Button v-if="canToggle(previewedVehicle) && previewedVehicle.status !== 'active'" variant="ghost-outline" size="icon-text" @click="openStatusDialog(previewedVehicle, 'active')">
                                <RiOctagonLine class="h-4 w-4" />Set Active
                            </Button>
                            <Button v-if="canToggle(previewedVehicle) && previewedVehicle.status !== 'inactive'" variant="ghost-outline" size="icon-text" @click="openStatusDialog(previewedVehicle, 'inactive')">
                                <RiShutDownLine class="h-4 w-4" />Set Inactive
                            </Button>
                            <Button v-if="canToggle(previewedVehicle) && previewedVehicle.status !== 'suspended'" variant="ghost-outline" size="icon-text" @click="openStatusDialog(previewedVehicle, 'suspended')">
                                <RiSpam2Line class="h-4 w-4" />Suspend
                            </Button>
                            <Button variant="destructive" size="icon-text" @click="openArchiveDialog(previewedVehicle)">
                                <RiArchive2Line class="h-4 w-4" />Archive
                            </Button>
                        </div>
                        <Button as-child variant="float-primary" size="icon-text"><Link :href="show({ vehicle: previewedVehicle.id }).url"><RiFileSearchLine class="h-4 w-4" />Review</Link></Button>
                    </div>
                </CardContent>
                <CardContent v-else class="flex min-h-0 flex-1 items-center justify-center">
                    <div class="max-w-60 space-y-1 text-center"><p class="text-base font-semibold text-custom-shadow">No vehicle selected</p><p class="text-sm text-custom-shadow/80">Click on a vehicle to preview.</p></div>
                </CardContent>
            </Card>
        </PanelLayout>

        <!-- TODO: use a dialog component here instead -->
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

        <VehicleStatusDialog
            v-model:open="statusDialogOpen"
            :vehicle="statusVehicle"
            :target-status="targetStatus"
        />


        <ImportVehicleDialog v-model:open="importOpen" @done="onImportDone" />
    </AppLayout>
</template>
