<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import { RestoreVehicleDialog } from '@/components/internal/vehicles';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { VehiclePreviewCard } from '@/components/internal/preview-cards';
import { Table, TableCard, TableColumn, TableContent, TableData, TableHeader, TableMoreButton, TableRow } from '@/components/ui/_table';
import { DropdownMenuItem, DropdownMenuLabel } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, show, trash } from '@/routes/vehicles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { RiArrowLeftLine, RiFilter2Line, RiRestartLine } from 'vue-remix-icons';
import { computed, ref, watch } from 'vue';
import { MainPanel, PanelLayout, SidePanel } from '@/components/ui/_panels';

type ArchivedVehicle = {
    id: number;
    vehicle_type?: { id: number; type_name: string } | null;
    plate_number?: string | null;
    body_number?: string | null;
    capacity?: string | number | null;
    deleted_at_human?: string | null;
    company?: { company_name?: string | null } | null;
    route?: { route_name?: string | null } | null;
    deleter?: { name?: string | null } | null;
};

const props = withDefaults(defineProps<{
    vehicles: {
        data: ArchivedVehicle[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number | null;
        to: number | null;
        total: number;
    };
    filters?: { search: string | null; vehicle_type_id: string | null; company: string | null; route: string | null };
    vehicleTypes: { id: number; type_name: string }[];
}>(), { filters: () => ({ search: null, vehicle_type_id: null, company: null, route: null }) });

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vehicles', href: index().url },
    { title: 'Archived Vehicles', href: trash().url },
];

const filterVehicleType = ref(props.filters.vehicle_type_id ?? '');
const filterCompany = ref(props.filters.company ?? '');
const filterRoute = ref(props.filters.route ?? '');
const filterOpen = ref(false);
const activeFilterCount = computed(() => [filterVehicleType.value, filterCompany.value, filterRoute.value].filter(Boolean).length);

function applyFilters() {
    router.get(trash().url, {
        search: props.filters.search || undefined,
        vehicle_type_id: filterVehicleType.value || undefined,
        company: filterCompany.value || undefined,
        route: filterRoute.value || undefined,
    }, { preserveScroll: true, preserveState: true, replace: true, only: ['vehicles', 'filters'] });
    filterOpen.value = false;
}

function clearFilters() {
    filterVehicleType.value = '';
    filterCompany.value = '';
    filterRoute.value = '';
    applyFilters();
}

const previewedVehicle = ref<ArchivedVehicle | null>(null);
const openMenuId = ref<number | null>(null);

// Drop the preview once its row leaves the list (e.g. after restoring it).
watch(() => props.vehicles.data, (rows) => {
    if (previewedVehicle.value && !rows.some((row) => row.id === previewedVehicle.value?.id)) {
        previewedVehicle.value = null;
    }
});
const restoreOpen = ref(false);
const selectedVehicle = ref<ArchivedVehicle | null>(null);

function openRestore(vehicle: ArchivedVehicle) {
    selectedVehicle.value = vehicle;
    restoreOpen.value = true;
}

</script>

<template>
    <Head title="Archived Vehicles" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
            <MainPanel>
                <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                    <CardHeader class="flex flex-row items-start gap-3">
                        <Button as-child variant="header-actions" size="icon"><Link :href="index().url" aria-label="Back to vehicles"><RiArrowLeftLine class="h-4 w-4" /></Link></Button>
                        <div class="flex min-w-0 flex-col"><CardTitle class="font-semibold">Archived Vehicles</CardTitle><CardDescription>Restore archived vehicles to the vehicles list.</CardDescription></div>
                    </CardHeader>

                    <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                        <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                            <div class="w-full">
                                <SearchInput
                                    :route="`${trash().url}?vehicle_type_id=${encodeURIComponent(filterVehicleType)}&company=${encodeURIComponent(filterCompany)}&route=${encodeURIComponent(filterRoute)}`"
                                    :initial-value="props.filters.search"
                                    placeholder="Search archived vehicles..."
                                    :only="['vehicles', 'filters', 'flash']"
                                    :debounce="350"
                                />
                            </div>
                            <Popover v-model:open="filterOpen">
                                <PopoverTrigger as-child>
                                    <Button variant="header-actions" size="icon-text" class="rounded-full" :class="activeFilterCount ? 'bg-custom-secondary/20 transition-all duration-200 hover:bg-custom-secondary/80 hover:text-custom-bg-light' : ''">
                                        <RiFilter2Line class="h-3.5 w-3.5" /><span class="hidden lg:flex">{{ activeFilterCount ? `${activeFilterCount} ${activeFilterCount === 1 ? 'filter' : 'filters'} active` : 'Filter' }}</span>
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent align="end">
                                    <div class="grid gap-y-2">
                                        <div class="flex flex-col gap-y-1"><p class="text-sm text-custom-shadow/80">Vehicle Type</p><select v-model="filterVehicleType" class="h-9 rounded-md border bg-custom-bg px-3 text-sm"><option value="">All types</option><option v-for="vehicleType in vehicleTypes" :key="vehicleType.id" :value="vehicleType.id">{{ vehicleType.type_name }}</option></select></div>
                                        <div class="flex flex-col gap-y-1"><p class="text-sm text-custom-shadow/80">Company</p><Input v-model="filterCompany" placeholder="Enter company name" class="bg-custom-bg" /></div>
                                        <div class="flex flex-col gap-y-1"><p class="text-sm text-custom-shadow/80">Route</p><Input v-model="filterRoute" placeholder="Enter route name" class="bg-custom-bg" /></div>
                                        <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light" />
                                        <div class="flex items-center justify-between"><Button v-if="activeFilterCount" size="sm" variant="destructive" @click="clearFilters">Clear</Button><div class="ml-auto flex gap-2"><Button size="sm" variant="ghost-outline" @click="filterOpen = false">Cancel</Button><Button size="sm" variant="float-primary" @click="applyFilters">Apply</Button></div></div>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        </div>

                        <TableCard :table-data-length="props.vehicles.data.length">
                            <Table v-if="props.vehicles.data.length > 0">
                                <TableHeader>
                                    <TableColumn>Company and Route</TableColumn>
                                    <TableColumn>Vehicle</TableColumn>
                                    <TableColumn>Plate</TableColumn>
                                    <TableColumn>Cap.</TableColumn>
                                    <TableColumn>Archived At</TableColumn>
                                    <TableColumn>Archived By</TableColumn>
                                </TableHeader>

                                <TableContent>
                                    <TableRow
                                        v-for="(vehicle, rowIndex) in props.vehicles.data"
                                        :key="vehicle.id"
                                        :class="[
                                            rowIndex === props.vehicles.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                            previewedVehicle?.id === vehicle.id ? 'bg-custom-secondary/10' : '',
                                        ]"
                                        @click.left="previewedVehicle = vehicle"
                                        @dblclick="router.visit(show({ vehicle: vehicle.id }).url)"
                                    >
                                        <TableData class="font-semibold">
                                            <div class="flex min-w-0 flex-col">
                                                <span class="truncate capitalize">{{ vehicle.company?.company_name || '—' }}</span>
                                                <span class="truncate text-xs text-custom-shadow/70">{{ vehicle.route?.route_name || 'No route assigned' }}</span>
                                            </div>
                                        </TableData>
                                        <TableData>
                                            <div class="flex min-w-0 flex-col">
                                                <span class="truncate text-sm font-medium">{{ vehicle.vehicle_type?.type_name ?? '—' }}</span>
                                                <span class="truncate text-xs text-custom-shadow/70">{{ vehicle.body_number || '—' }}</span>
                                            </div>
                                        </TableData>
                                        <TableData>
                                            <span class="rounded bg-custom-bg px-2 py-0.5 font-mono text-xs font-semibold text-custom-shadow dark:bg-custom-bg-light">{{ vehicle.plate_number || '—' }}</span>
                                        </TableData>
                                        <TableData class="tabular-nums">{{ vehicle.capacity || '—' }}</TableData>
                                        <TableData>{{ vehicle.deleted_at_human || '—' }}</TableData>
                                        <TableData><span class="truncate">{{ vehicle.deleter?.name || '—' }}</span></TableData>

                                        <TableMoreButton
                                            :open="openMenuId === vehicle.id"
                                            @update:open="(value) => (openMenuId = value ? vehicle.id : null)"
                                        >
                                            <DropdownMenuLabel>{{ vehicle.plate_number || 'Vehicle' }}</DropdownMenuLabel>
                                            <DropdownMenuItem class="group cursor-pointer" @click="openRestore(vehicle)">
                                                <RiRestartLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                Restore Vehicle
                                            </DropdownMenuItem>
                                        </TableMoreButton>
                                    </TableRow>
                                </TableContent>
                            </Table>

                            <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center"><div class="flex w-full max-w-md flex-col items-center gap-2"><img :src="emptyRafikiUrl" alt="" class="w-1/3 object-contain opacity-90" aria-hidden="true" /><div class="space-y-1"><p class="text-base font-semibold text-custom-shadow">No archived vehicles found</p><p class="text-sm text-custom-shadow/80">{{ props.filters.search || activeFilterCount ? 'Try adjusting your search or filters.' : 'Nothing has been archived yet.' }}</p></div></div></div>
                        </TableCard>

                        <InertiaPagination :links="props.vehicles.links" :meta="{ from: props.vehicles.from, to: props.vehicles.to, total: props.vehicles.total }" />
                    </CardContent>
                </Card>
            </MainPanel>
            
            <SidePanel v-if="previewedVehicle" class="hidden lg:flex">
                <VehiclePreviewCard :vehicle="previewedVehicle" archived @close="previewedVehicle = null" />
            </SidePanel>
        </PanelLayout>
        <RestoreVehicleDialog v-if="selectedVehicle" v-model:open="restoreOpen" :vehicle="selectedVehicle" />
    </AppLayout>
</template>
