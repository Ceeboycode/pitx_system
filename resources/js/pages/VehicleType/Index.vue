<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';

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

import CreateVehicleTypeDialog from '@/components/vehicleType/CreateVehicleTypeDialog.vue';
import EditVehicleTypeDialog from '@/components/vehicleType/EditVehicleTypeDialog.vue';
import ToggleVehicleTypeStatusDialog from '@/components/vehicleType/ToggleVehicleTypeStatusDialog.vue';
import DeleteVehicleTypeDialog from '@/components/vehicleType/DeleteVehicleTypeDialog.vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { index, edit, show } from '@/routes/vehicle-types';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { PanelLayout } from '@/components/ui/_panels';

import {
    RiFilter2Line,
    RiMore2Line,
    RiEditLine,
    RiAddLine,
    RiShutDownLine,
    RiCloseLine,
    RiDeleteBinLine,
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
            links: any[];
            from: number | null;
            to: number | null;
            total: number;
        };
        filters?: {
            search: string | null;
            status: string | null;
        };
        canDelete: boolean;
    }>(),
    { filters: () => ({ search: null, status: null }) },
);

const filterStatus = ref<string>(
    props.filters?.status ? String(props.filters.status) : 'all'
);
const filterOpen   = ref(false);

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

const createOpen   = ref(false);
const editOpen   = ref(false);
const selectedVehicleType = ref<VehicleType | null>(null);

const toggleOpen = ref(false);
const togglingVehicleType = ref<VehicleType | null>(null);
const deleteOpen = ref(false);
const deletingVehicleType = ref<VehicleType | null>(null);

function statusClass(is_active: boolean): string {
    return is_active
        ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
        : 'bg-slate-100 text-slate-500 border-0';
}

function statusDot(is_active: boolean): string {
    return is_active ? 'bg-emerald-500' : 'bg-slate-400';
}

function openEditDialog(vehicleType: VehicleType) {
    selectedVehicleType.value = vehicleType;
    editOpen.value = true;
}

function openToggleDialog(vehicleType: VehicleType) {
    togglingVehicleType.value = vehicleType;
    toggleOpen.value = true;
}

function openDeleteDialog(vehicleType: VehicleType) {
    deletingVehicleType.value = vehicleType;
    deleteOpen.value = true;
}
</script>

<template>
    <Head title="Vehicle Types" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row gap-2">
                    <div class="flex flex-col">
                        <CardTitle class="flex items-center gap-2">
                            <span class="font-semibold">Vehicle Types</span>
                        </CardTitle>
                        <CardDescription class="">List of all vehicle types in the system.</CardDescription>
                    </div>
                    <div class="flex flex-1 gap-2 justify-end">
                        <div class="lg:flex items-center gap-2 sm:justify-end">
                            <Button
                                variant="float-primary"
                                @click="createOpen = true"
                                class="hidden lg:flex"
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
                                            <RiAddLine class="h-4 w-4 hover:text-custom-bg-light" />
                                            Add Vehicle Type
                                        </button>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
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
                                <PopoverTrigger
                                    as-child
                                >
                                    <Button
                                        variant="header-actions"
                                        size="icon-text"
                                        class="rounded-full "
                                        :class="
                                            activeFilterCount > 0
                                                ? ' bg-custom-secondary/20 hover:text-custom-bg-light hover:bg-custom-secondary/80 transition-all duration-200'
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

                                <PopoverContent
                                    class="w-80 border-custom-bg-dark bg-custom-bg p-4 dark:border-custom-bg-light dark:bg-custom-bg-dark"
                                    align="end"
                                >
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
                                                class=""
                                                @click="clearFilters"
                                            >
                                                Clear
                                            </Button>

                                            <div class="flex ml-auto items-center gap-2">
                                                <Button
                                                    variant="ghost-outline"
                                                    size="sm"
                                                    class=""
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
                            'flex min-h-0 flex-1 max-h-fit flex-col overflow-hidden border border-custom-bg-dark dark:border-custom-bg-light py-0 shadow-none dark:inset-shadow-none',
                            props.vehicleTypes.data.length === 0 ? 'border-dashed' : 'border-solid',
                        ]"
                    >
                        <div v-if="props.vehicleTypes.data.length > 0" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                            <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                                <div class="grid grid-cols-3 gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                    <div class="col-span-1 flex h-10 font-semibold items-center justify-start px-0 pl-3 text-left text-xs uppercase tracking-widest text-custom-shadow/80">Vehicle Type</div>
                                    <div class="col-span-1 flex h-10 font-semibold items-center justify-start px-0 text-left text-xs uppercase tracking-widest text-custom-shadow/80">Status</div>
                                    <div class="col-span-1 flex h-10 font-semibold items-center justify-end px-0 pr-3 text-left text-xs uppercase tracking-widest text-custom-shadow/80">Actions</div>
                                </div>
                            </div>

                            <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                <template
                                    v-for="(vehicleType, index) in props.vehicleTypes.data"
                                    :key="vehicleType.id"
                                >
                                <div class="grid grid-cols-3 gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light last:border-0 hover:bg-custom-bg/50 dark:hover:bg-custom-bg-light/50 transition-colors">
                                    <div class="col-span-1 flex min-h-12 items-center justify-start px-3 text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium capitalize text-custom-shadow">{{ vehicleType.type_name }}</span>
                                        </div>
                                    </div>

                                    <div class="col-span-1 flex items-center justify-start px-0 text-sm">
                                        <Badge
                                            variant="outline"
                                            :class="['h-6 capitalize', statusClass(vehicleType.is_active)]"
                                        >
                                            <span
                                                :class="['mr-1.5 h-1.5 w-1.5 rounded-full', statusDot(vehicleType.is_active)]"
                                                aria-hidden="true"
                                            ></span>
                                            {{ vehicleType.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </div>

                                    <div class="col-span-1 flex items-center justify-end px-3">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="table-more"
                                                    size="icon"
                                                    class="h-8 w-8"
                                                >
                                                    <RiMore2Line class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent
                                                align="end"
                                            >
                                                <DropdownMenuItem class="group cursor-pointer" @click="openEditDialog(vehicleType)">
                                                    <RiEditLine class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow transition-all duration-200" />
                                                    <span class="text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow transition-all duration-200">
                                                        Edit
                                                    </span>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem class="group cursor-pointer" @click="openToggleDialog(vehicleType)">
                                                    <RiShutDownLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                                    <span class="text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow">
                                                        {{ vehicleType.is_active ? 'Set as Inactive' : 'Set as Active' }}
                                                    </span>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem v-if="canDelete" class="group cursor-pointer text-destructive" @click="openDeleteDialog(vehicleType)">
                                                    <RiDeleteBinLine class="h-4 w-4" />
                                                    Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                                </template>
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
                                    <p class="text-custom-shadow text-base font-semibold">No vehicle types found</p>
                                    <p class="text-custom-shadow/80 text-sm">
                                        {{ activeFilterCount > 0 ? 'Try adjusting or clearing your filters.' : 'Try adjusting your search or add a new vehicle type.' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <InertiaPagination
                        :links="props.vehicleTypes.links"
                        :meta="{ from: props.vehicleTypes.from, to: props.vehicleTypes.to, total: props.vehicleTypes.total }"
                    />
                </CardContent>
            </Card>
        </PanelLayout>

        <CreateVehicleTypeDialog v-model:open="createOpen" />
        <EditVehicleTypeDialog
            v-if="selectedVehicleType"
            v-model:open="editOpen"
            :vehicle_type="selectedVehicleType"
        />
        <ToggleVehicleTypeStatusDialog v-model:open="toggleOpen" :vehicle_type="togglingVehicleType" />
        <DeleteVehicleTypeDialog v-if="deletingVehicleType" v-model:open="deleteOpen" :vehicle_type="deletingVehicleType" />
    </AppLayout>
</template>
