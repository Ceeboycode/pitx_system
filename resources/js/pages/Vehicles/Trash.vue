<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import RestoreVehicleDialog from '@/components/vehicle/RestoreVehicleDialog.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, trash } from '@/routes/vehicles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { RiArrowLeftSLine, RiFilter2Line, RiMore2Line, RiRestartLine } from 'vue-remix-icons';
import { computed, ref } from 'vue';

type ArchivedVehicle = {
    id: number;
    vehicle_type?: string | null;
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
    filters?: { search: string | null; vehicle_type: string | null; company: string | null; route: string | null };
}>(), { filters: () => ({ search: null, vehicle_type: null, company: null, route: null }) });

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vehicles', href: index().url },
    { title: 'Archived Vehicles', href: trash().url },
];

const filterVehicleType = ref(props.filters.vehicle_type ?? '');
const filterCompany = ref(props.filters.company ?? '');
const filterRoute = ref(props.filters.route ?? '');
const filterOpen = ref(false);
const activeFilterCount = computed(() => [filterVehicleType.value, filterCompany.value, filterRoute.value].filter(Boolean).length);

function applyFilters() {
    router.get(trash().url, {
        search: props.filters.search || undefined,
        vehicle_type: filterVehicleType.value || undefined,
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

const restoreOpen = ref(false);
const selectedVehicle = ref<ArchivedVehicle | null>(null);

function openRestore(vehicle: ArchivedVehicle) {
    selectedVehicle.value = vehicle;
    restoreOpen.value = true;
}

function humanize(text?: string | null) {
    if (!text) return '—';
    return text.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
}
</script>

<template>
    <Head title="Archived Vehicles" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4">
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row items-start gap-3">
                    <Button as-child variant="header-actions" size="icon"><Link :href="index().url" aria-label="Back to vehicles"><RiArrowLeftSLine class="h-4 w-4" /></Link></Button>
                    <div class="flex min-w-0 flex-col"><CardTitle class="font-semibold">Archived Vehicles</CardTitle><CardDescription>Restore archived vehicles to the vehicles list.</CardDescription></div>
                </CardHeader>

                <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                    <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                        <div class="w-full">
                            <SearchInput
                                :route="`${trash().url}?vehicle_type=${encodeURIComponent(filterVehicleType)}&company=${encodeURIComponent(filterCompany)}&route=${encodeURIComponent(filterRoute)}`"
                                :initial-value="props.filters.search"
                                placeholder="Search archived vehicles..."
                                :only="['vehicles', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>
                        <Popover v-model:open="filterOpen">
                            <PopoverTrigger as-child>
                                <Button variant="header-actions" size="icon-text" class="rounded-full" :class="activeFilterCount ? 'bg-custom-secondary/20 transition-all duration-300 hover:bg-custom-secondary/80 hover:text-custom-bg-light' : ''">
                                    <RiFilter2Line class="h-3.5 w-3.5" /><span class="hidden lg:flex">{{ activeFilterCount ? `${activeFilterCount} ${activeFilterCount === 1 ? 'filter' : 'filters'} active` : 'Filter' }}</span>
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent align="end">
                                <div class="grid gap-y-2">
                                    <div class="flex flex-col gap-y-1"><p class="text-sm text-custom-shadow/80">Vehicle Type</p><Input v-model="filterVehicleType" placeholder="e.g. bus" class="bg-custom-bg" /></div>
                                    <div class="flex flex-col gap-y-1"><p class="text-sm text-custom-shadow/80">Company</p><Input v-model="filterCompany" placeholder="Enter company name" class="bg-custom-bg" /></div>
                                    <div class="flex flex-col gap-y-1"><p class="text-sm text-custom-shadow/80">Route</p><Input v-model="filterRoute" placeholder="Enter route name" class="bg-custom-bg" /></div>
                                    <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light" />
                                    <div class="flex items-center justify-between"><Button v-if="activeFilterCount" size="sm" variant="destructive" @click="clearFilters">Clear</Button><div class="ml-auto flex gap-2"><Button size="sm" variant="ghost-outline" @click="filterOpen = false">Cancel</Button><Button size="sm" variant="float-primary" @click="applyFilters">Apply</Button></div></div>
                                </div>
                            </PopoverContent>
                        </Popover>
                    </div>

                    <Card :class="['flex min-h-0 max-h-fit flex-1 flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none', props.vehicles.data.length ? 'border-solid' : 'border-dashed']">
                        <div v-if="props.vehicles.data.length" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                            <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                                <div class="grid grid-cols-[1.5fr_1.2fr_1fr_.6fr_1fr_1fr_5rem] gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                    <div class="flex h-10 items-center pl-3 text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Company and Route</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Vehicle</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Plate</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Cap.</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Archived At</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Archived By</div>
                                    <div class="flex h-10 items-center justify-end pr-3 text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Actions</div>
                                </div>
                            </div>
                            <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                <div v-for="(vehicle, rowIndex) in props.vehicles.data" :key="vehicle.id" :class="['grid grid-cols-[1.5fr_1.2fr_1fr_.6fr_1fr_1fr_5rem] items-center gap-2 border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light', rowIndex === props.vehicles.data.length - 1 ? 'rounded-b-md border-b-0' : '']">
                                    <div class="flex min-w-0 flex-col py-2 pl-3"><span class="truncate font-semibold capitalize">{{ vehicle.company?.company_name || '—' }}</span><span class="truncate text-xs text-custom-shadow/70">{{ vehicle.route?.route_name || 'No route assigned' }}</span></div>
                                    <div class="flex min-w-0 flex-col py-2"><span class="truncate text-sm font-medium">{{ humanize(vehicle.vehicle_type) }}</span><span class="truncate text-xs text-custom-shadow/70">{{ vehicle.body_number || '—' }}</span></div>
                                    <div class="flex py-2"><span class="rounded bg-custom-bg px-2 py-0.5 font-mono text-xs font-semibold text-custom-shadow dark:bg-custom-bg-light">{{ vehicle.plate_number || '—' }}</span></div>
                                    <div class="flex py-2 text-sm tabular-nums">{{ vehicle.capacity || '—' }}</div>
                                    <div class="flex py-2 text-sm">{{ vehicle.deleted_at_human || '—' }}</div>
                                    <div class="flex min-w-0 py-2 text-sm"><span class="truncate">{{ vehicle.deleter?.name || '—' }}</span></div>
                                    <div class="flex justify-end py-2 pr-3">
                                        <DropdownMenu><DropdownMenuTrigger as-child><Button variant="table-more" size="icon-more"><RiMore2Line class="h-4 w-4" /></Button></DropdownMenuTrigger><DropdownMenuContent align="end"><DropdownMenuLabel>{{ vehicle.plate_number || 'Vehicle' }}</DropdownMenuLabel><DropdownMenuItem class="group" @click="openRestore(vehicle)"><RiRestartLine class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />Restore Vehicle</DropdownMenuItem></DropdownMenuContent></DropdownMenu>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center"><div class="flex w-full max-w-md flex-col items-center gap-2"><img :src="emptyRafikiUrl" alt="" class="w-1/3 object-contain opacity-90" aria-hidden="true" /><div class="space-y-1"><p class="text-base font-semibold text-custom-shadow">No archived vehicles found</p><p class="text-sm text-custom-shadow/80">{{ props.filters.search || activeFilterCount ? 'Try adjusting your search or filters.' : 'Nothing has been archived yet.' }}</p></div></div></div>
                    </Card>

                    <InertiaPagination :links="props.vehicles.links" :meta="{ from: props.vehicles.from, to: props.vehicles.to, total: props.vehicles.total }" />
                </CardContent>
            </Card>
        </div>
        <RestoreVehicleDialog v-if="selectedVehicle" v-model:open="restoreOpen" :vehicle="selectedVehicle" />
    </AppLayout>
</template>
