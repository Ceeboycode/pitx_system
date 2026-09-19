<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import RestoreVehicleTypeDialog from '@/components/internal/vehicleType/RestoreVehicleTypeDialog.vue';
import ForceDeleteVehicleTypeDialog from '@/components/internal/vehicleType/ForceDeleteVehicleTypeDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, trash } from '@/routes/vehicle-types';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { RiArrowLeftSLine, RiDeleteBin7Line, RiMore2Line, RiRestartLine } from 'vue-remix-icons';
import { ref } from 'vue';
import { PanelLayout, MainPanel, SidePanel } from '@/components/ui/_panels';
import { can } from '@/lib/can';

type ArchivedVehicleType = {
    id: number;
    type_name: string;
    is_active: boolean;
    deleted_at_human?: string | null;
    deleter?: { name?: string | null } | null;
};

const props = withDefaults(defineProps<{
    vehicleTypes: {
        data: ArchivedVehicleType[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number | null;
        to: number | null;
        total: number;
    };
    filters?: { search: string | null };
}>(), { filters: () => ({ search: null }) });

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vehicle Types', href: index().url },
    { title: 'Archived Vehicle Types', href: trash().url },
];

const canRestore = can('vehicle_types.restore');
const canForceDelete = can('vehicle_types.forceDelete');

const restoreOpen = ref(false);
const forceDeleteOpen = ref(false);
const selectedVehicleType = ref<ArchivedVehicleType | null>(null);

function openRestore(vehicleType: ArchivedVehicleType) {
    selectedVehicleType.value = vehicleType;
    restoreOpen.value = true;
}

function openForceDelete(vehicleType: ArchivedVehicleType) {
    selectedVehicleType.value = vehicleType;
    forceDeleteOpen.value = true;
}
</script>

<template>
    <Head title="Archived Vehicle Types" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
            <MainPanel>
                <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                    <CardHeader class="flex flex-row items-start gap-3">
                        <Button as-child variant="header-actions" size="icon"><Link :href="index().url" aria-label="Back to vehicle types"><RiArrowLeftSLine class="h-4 w-4 shrink-0" /></Link></Button>
                        <div class="flex min-w-0 flex-col"><CardTitle class="font-semibold">Archived Vehicle Types</CardTitle><CardDescription>Restore archived vehicle types to the list.</CardDescription></div>
                    </CardHeader>

                    <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="trash().url"
                                :initial-value="props.filters.search"
                                placeholder="Search archived vehicle types..."
                                :only="['vehicleTypes', 'filters']"
                                :debounce="350"
                            />
                        </div>

                        <Card :class="['flex min-h-0 max-h-fit flex-1 flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none', props.vehicleTypes.data.length ? 'border-solid' : 'border-dashed']">
                            <div v-if="props.vehicleTypes.data.length" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                                <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                                    <div class="grid grid-cols-[1.5fr_1fr_1fr_1fr_5rem] gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                        <div class="flex h-10 items-center pl-3 text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Vehicle Type</div>
                                        <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Status</div>
                                        <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Archived At</div>
                                        <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Archived By</div>
                                        <div class="flex h-10 items-center justify-end pr-3 text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Actions</div>
                                    </div>
                                </div>
                                <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                    <div v-for="(vehicleType, rowIndex) in props.vehicleTypes.data" :key="vehicleType.id" :class="['grid grid-cols-[1.5fr_1fr_1fr_1fr_5rem] items-center gap-2 border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light', rowIndex === props.vehicleTypes.data.length - 1 ? 'rounded-b-md border-b-0' : '']">
                                        <div class="flex min-w-0 py-2 pl-3"><span class="truncate font-semibold capitalize">{{ vehicleType.type_name }}</span></div>
                                        <div class="flex py-2">
                                            <Badge :class="['gap-1.5', vehicleType.is_active ? 'border-emerald-200 bg-emerald-100 text-emerald-700' : 'border-0 bg-slate-100 text-slate-500']">
                                                <span :class="['h-1.5 w-1.5 rounded-full', vehicleType.is_active ? 'bg-emerald-500' : 'bg-slate-400']" />
                                                {{ vehicleType.is_active ? 'Active' : 'Inactive' }}
                                            </Badge>
                                        </div>
                                        <div class="flex py-2 text-sm">{{ vehicleType.deleted_at_human || '—' }}</div>
                                        <div class="flex min-w-0 py-2 text-sm"><span class="truncate">{{ vehicleType.deleter?.name || '—' }}</span></div>
                                        <div class="flex justify-end py-2 pr-3">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger as-child><Button variant="table-more" size="icon-more"><RiMore2Line class="h-4 w-4 shrink-0" /></Button></DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuLabel>{{ vehicleType.type_name }}</DropdownMenuLabel>
                                                    <DropdownMenuItem v-if="canRestore" class="group cursor-pointer" @click="openRestore(vehicleType)">
                                                        <RiRestartLine class="h-4 w-4 shrink-0 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                        Restore
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem v-if="canForceDelete" class="group cursor-pointer text-destructive" @click="openForceDelete(vehicleType)">
                                                        <RiDeleteBin7Line class="h-4 w-4 shrink-0" />
                                                        Delete Permanently
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center"><div class="flex w-full max-w-md flex-col items-center gap-2"><img :src="emptyRafikiUrl" alt="" class="w-1/3 object-contain opacity-90" aria-hidden="true" /><div class="space-y-1"><p class="text-base font-semibold text-custom-shadow">No archived vehicle types found</p><p class="text-sm text-custom-shadow/80">{{ props.filters.search ? 'Try adjusting your search.' : 'Nothing has been archived yet.' }}</p></div></div></div>
                        </Card>

                        <InertiaPagination :links="props.vehicleTypes.links" :meta="{ from: props.vehicleTypes.from, to: props.vehicleTypes.to, total: props.vehicleTypes.total }" />
                    </CardContent>
                </Card>
            </MainPanel>

            <SidePanel>

            </SidePanel>
        </PanelLayout>
        
        <RestoreVehicleTypeDialog v-if="selectedVehicleType" v-model:open="restoreOpen" :vehicle-type="selectedVehicleType" />
        <ForceDeleteVehicleTypeDialog v-if="selectedVehicleType" v-model:open="forceDeleteOpen" :vehicle-type="selectedVehicleType" />
    </AppLayout>
</template>
