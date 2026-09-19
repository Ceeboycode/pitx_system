<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';

import {
    Table,
    TableColumn,
    TableHeader,
    TableContent,
    TableRow,
    TableCard,
    TableData,
    TableMoreButton,
} from '@/components/ui/_table';
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

import CreateVehicleTypeDialog from '@/components/internal/vehicleType/CreateVehicleTypeDialog.vue';
import ToggleVehicleTypeStatusDialog from '@/components/internal/vehicleType/ToggleVehicleTypeStatusDialog.vue';
import ArchiveVehicleTypeDialog from '@/components/internal/vehicleType/ArchiveVehicleTypeDialog.vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { index, edit, trash } from '@/routes/vehicle-types';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { PanelLayout, MainPanel, SidePanel } from '@/components/ui/_panels';
import { can } from '@/lib/can';

import {
    RiAddLine,
    RiArchive2Line,
    RiCloseLine,
    RiEditLine,
    RiFilter2Line,
    RiMore2Line,
    RiShutDownLine,
} from 'vue-remix-icons';

import { computed, ref } from 'vue';

interface VehicleType {
    id: number;
    type_name: string;
    is_active: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Vehicle Types', href: index().url }];

const props = withDefaults(
    defineProps<{
        vehicleTypes: {
            data: VehicleType[];
            links: { url: string | null; label: string; active: boolean }[];
            from: number | null;
            to: number | null;
            total: number;
        };
        filters?: {
            search: string | null;
            status: string | null;
        };
    }>(),
    { filters: () => ({ search: null, status: null }) },
);

const canUpdate = can('vehicle_types.update');
const canArchive = can('vehicle_types.archive');

const filterStatus = ref<string>(
    props.filters?.status ? String(props.filters.status) : 'all'
);
const filterOpen = ref(false);

const activeFilterCount = computed(() => {
    let count = 0;
    if (filterStatus.value && filterStatus.value !== 'all') count++;
    return count;
});

function applyFilters() {
    router.get(
        index().url,
        {
            search: props.filters?.search || undefined,
            status: filterStatus.value === 'all' ? undefined : filterStatus.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['vehicleTypes', 'filters'],
        },
    );
    filterOpen.value = false;
}

function clearFilters() {
    filterStatus.value = 'all';

    router.get(
        index().url,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['vehicleTypes', 'filters'],
        },
    );

    filterOpen.value = false;
}

const createOpen = ref(false);
const toggleOpen = ref(false);
const togglingVehicleType = ref<VehicleType | null>(null);
const archiveOpen = ref(false);
const archivingVehicleType = ref<VehicleType | null>(null);
const previewedVehicleType = ref<VehicleType | null>(null);

function openPreview(vehicleType: VehicleType) {
    previewedVehicleType.value = vehicleType;
}

function openToggleDialog(vehicleType: VehicleType) {
    togglingVehicleType.value = vehicleType;
    toggleOpen.value = true;
}

function openArchiveDialog(vehicleType: VehicleType) {
    archivingVehicleType.value = vehicleType;
    archiveOpen.value = true;
}

const openMenus = ref<Record<number, boolean>>({});
</script>

<template>
    <Head title="Vehicle Types" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
            <MainPanel>
                <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                    <CardHeader class="flex flex-row gap-2">
                        <div class="flex flex-col">
                            <CardTitle class="flex items-center gap-2">
                                <span class="font-semibold">Vehicle Types</span>
                            </CardTitle>
                            <CardDescription>List of all vehicle types in the system.</CardDescription>
                        </div>
                        <div class="flex flex-1 justify-end gap-2">
                            <div class="lg:flex items-center gap-2 sm:justify-end">
                                <Button
                                    variant="float-primary"
                                    class="hidden lg:flex"
                                    @click="createOpen = true"
                                >
                                    <RiAddLine class="h-4 w-4 shrink-0" />
                                    <span>Add Vehicle Type</span>
                                </Button>
                                <DropdownMenu class="w-fit">
                                    <DropdownMenuTrigger as-child class="m-0">
                                        <div class="inline-flex">
                                            <Button
                                                variant="header-actions"
                                                class="text-custom-shadow"
                                                size="icon"
                                                aria-label="Open vehicle type actions"
                                            >
                                                <RiMore2Line class="h-4 w-4 shrink-0" />
                                            </Button>
                                        </div>
                                    </DropdownMenuTrigger>

                                    <DropdownMenuContent align="end" class="w-fit">
                                        <DropdownMenuItem
                                            as-child
                                            class="cursor-pointer lg:hidden"
                                        >
                                            <button
                                                type="button"
                                                class="flex items-center"
                                                @click="createOpen = true"
                                            >
                                                <RiAddLine class="h-4 w-4 shrink-0" />
                                                Add Vehicle Type
                                            </button>
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

                    <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 pt-2">
                        <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                            <div class="w-full max-w-sm">
                                <SearchInput
                                    :route="index().url"
                                    :initial-value="props.filters?.search"
                                    placeholder="Search vehicle types..."
                                    :only="['vehicleTypes', 'filters']"
                                    :debounce="350"
                                />
                            </div>

                            <div class="w-fit flex gap-2 flex-row lg:items-center lg:justify-between">
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

                                    <PopoverContent align="end">
                                        <div class="grid gap-y-2">
                                            <div class="flex flex-col gap-y-1">
                                                <p class="text-sm text-custom-shadow/80">
                                                    Status
                                                </p>
                                                <Select v-model="filterStatus">
                                                    <SelectTrigger class="w-full">
                                                        <SelectValue placeholder="Any status" class="flex justify-start" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="all" class="cursor-pointer">
                                                            Any status
                                                        </SelectItem>
                                                        <SelectItem value="active" class="cursor-pointer">
                                                            Active
                                                        </SelectItem>
                                                        <SelectItem value="inactive" class="cursor-pointer">
                                                            Inactive
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">

                                            <div class="w-full justify-between items-center flex flex-row">
                                                <Button
                                                    v-if="activeFilterCount > 0"
                                                    size="sm"
                                                    variant="destructive"
                                                    @click="clearFilters"
                                                >
                                                    Clear
                                                </Button>

                                                <div class="flex ml-auto items-center gap-2">
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

                        <TableCard :table-data-length="vehicleTypes.data.length">
                            <Table v-if="vehicleTypes.data.length > 0">
                                <TableHeader>
                                    <TableColumn>Vehicle Type</TableColumn>
                                    <TableColumn>Status</TableColumn>
                                </TableHeader>

                                <TableContent>
                                    <TableRow
                                        v-for="(vehicleType, rowIndex) in vehicleTypes.data"
                                        :key="vehicleType.id"
                                        :class="[
                                            rowIndex === vehicleTypes.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                            previewedVehicleType?.id === vehicleType.id ? 'bg-custom-secondary/10' : '',
                                        ]"
                                        :status="vehicleType.is_active ? 'default' : 'inactive'"
                                        @click.left="openPreview(vehicleType)"
                                        @dblclick="router.visit(edit(vehicleType.id).url)"
                                    >
                                        <TableData class="pl-3 font-semibold">
                                            <span class="truncate capitalize">{{ vehicleType.type_name }}</span>
                                        </TableData>

                                        <TableData>
                                            <Badge :class="['gap-1.5', vehicleType.is_active ? 'border-emerald-200 bg-emerald-100 text-emerald-700' : 'border-0 bg-slate-100 text-slate-500']">
                                                <span :class="['h-1.5 w-1.5 rounded-full', vehicleType.is_active ? 'bg-emerald-500' : 'bg-slate-400']" />
                                                {{ vehicleType.is_active ? 'Active' : 'Inactive' }}
                                            </Badge>
                                        </TableData>

                                        <TableMoreButton
                                            :open="openMenus[vehicleType.id] ?? false"
                                            @update:open="(value) => (openMenus[vehicleType.id] = value)"
                                        >
                                            <DropdownMenuLabel>
                                                {{ vehicleType.type_name }}
                                            </DropdownMenuLabel>

                                            <DropdownMenuItem as-child class="group cursor-pointer">
                                                <Link :href="edit(vehicleType.id).url" class="flex items-center">
                                                    <RiEditLine class="h-4 w-4 shrink-0 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                                    Edit
                                                </Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-if="canUpdate"
                                                class="group cursor-pointer"
                                                @click="openToggleDialog(vehicleType)"
                                            >
                                                <RiShutDownLine class="h-4 w-4 shrink-0 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                                {{ vehicleType.is_active ? 'Inactivate' : 'Activate' }}
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-if="canArchive"
                                                class="group cursor-pointer text-destructive"
                                                @click="openArchiveDialog(vehicleType)"
                                            >
                                                <RiArchive2Line class="h-4 w-4 shrink-0" />
                                                Archive
                                            </DropdownMenuItem>
                                        </TableMoreButton>
                                    </TableRow>
                                </TableContent>
                            </Table>

                            <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                                <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                                    <img
                                        :src="emptyRafikiUrl"
                                        alt=""
                                        class="w-1/3 object-contain opacity-90"
                                        aria-hidden="true"
                                    />
                                    <div class="space-y-1">
                                        <p class="text-custom-shadow text-base font-semibold">No vehicle types found</p>
                                        <p class="text-custom-shadow/80 text-sm">
                                            {{ activeFilterCount > 0 ? 'Try adjusting or clearing your filters.' : 'Try adjusting your search or add a new vehicle type.' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </TableCard>

                        <InertiaPagination
                            :links="vehicleTypes.links"
                            :meta="{ from: vehicleTypes.from, to: vehicleTypes.to, total: vehicleTypes.total }"
                        />
                    </CardContent>
                </Card>
            </MainPanel>
            
            <SidePanel>
                <Card class="hidden min-h-0 lg:flex lg:h-full lg:w-full">
                    <CardHeader v-if="previewedVehicleType" class="flex flex-row items-start justify-between gap-3">
                        <div class="min-w-0">
                            <CardTitle class="truncate capitalize">{{ previewedVehicleType.type_name }}</CardTitle>
                            <CardDescription>Preview</CardDescription>
                        </div>
                        <Button variant="header-actions" size="icon" class="h-8 w-8 shrink-0 rounded-full" @click="previewedVehicleType = null">
                            <RiCloseLine class="h-4 w-4 shrink-0" />
                        </Button>
                    </CardHeader>

                    <CardContent v-if="previewedVehicleType" class="no-scrollbar min-h-0 flex-1 space-y-2 overflow-y-auto pt-2">
                        <div class="space-y-3 pt-2">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-semibold text-custom-shadow">Status</span>
                                <Badge :class="['gap-1.5', previewedVehicleType.is_active ? 'border-emerald-200 bg-emerald-100 text-emerald-700' : 'border-0 bg-slate-100 text-slate-500']">
                                    <span :class="['h-1.5 w-1.5 rounded-full', previewedVehicleType.is_active ? 'bg-emerald-500' : 'bg-slate-400']" />
                                    {{ previewedVehicleType.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </div>
                        </div>
                        <hr class="my-4 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap gap-2">
                                <Button v-if="canUpdate" variant="ghost-outline" size="icon-text" @click="openToggleDialog(previewedVehicleType)">
                                    <RiShutDownLine class="h-4 w-4 shrink-0" />{{ previewedVehicleType.is_active ? 'Inactivate' : 'Activate' }}
                                </Button>
                                <Button v-if="canArchive" variant="destructive" size="icon-text" @click="openArchiveDialog(previewedVehicleType)">
                                    <RiArchive2Line class="h-4 w-4 shrink-0" />Archive
                                </Button>
                            </div>
                            <Button as-child variant="float-primary" size="icon-text"><Link :href="edit(previewedVehicleType.id).url"><RiEditLine class="h-4 w-4 shrink-0" />Edit</Link></Button>
                        </div>
                    </CardContent>
                    <CardContent v-else class="flex min-h-0 flex-1 items-center justify-center">
                        <div class="max-w-60 space-y-1 text-center"><p class="text-base font-semibold text-custom-shadow">No vehicle type selected</p><p class="text-sm text-custom-shadow/80">Click on a vehicle type to preview.</p></div>
                    </CardContent>
                </Card>
            </SidePanel>
            
        </PanelLayout>

        <CreateVehicleTypeDialog v-model:open="createOpen" />
        <ToggleVehicleTypeStatusDialog v-model:open="toggleOpen" :vehicle_type="togglingVehicleType" />
        <ArchiveVehicleTypeDialog v-model:open="archiveOpen" :vehicle-type="archivingVehicleType" />
    </AppLayout>
</template>
