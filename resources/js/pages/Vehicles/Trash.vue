<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import { can } from '@/lib/can';

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
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

import AppLayout from '@/layouts/AppLayout.vue';
import { index, trash } from '@/routes/vehicles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

import {
    Archive,
    ArrowLeft,
    Bus,
    MoreHorizontal,
    RotateCcw,
    Route as RouteIcon,
    Trash2,
} from 'lucide-vue-next';

import { computed, ref, watch } from 'vue';

type VehicleTypeValue =
    | string
    | {
          type_name?: string | null;
      }
    | null
    | undefined;

type VehicleItem = {
    id: number;
    vehicle_type?: VehicleTypeValue;
    plate_number?: string | null;
    body_number?: string | null;
    capacity?: string | number | null;
    deleted_at_human?: string | null;
    company?: { company_name?: string | null } | null;
    route?: { route_name?: string | null } | null;
    deleter?: { name?: string | null } | null;
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
    { title: 'Archived Vehicles', href: trash().url },
];

const restoreOpen = ref(false);
const forceDeleteOpen = ref(false);
const selectedVehicle = ref<VehicleItem | null>(null);

const canRestoreVehicle = can('vehicles.restore');
const canForceDeleteVehicle = can('vehicles.forceDelete');

const hasRowActions = computed(() => canRestoreVehicle || canForceDeleteVehicle);

watch(restoreOpen, (value) => {
    if (!value && !forceDeleteOpen.value) selectedVehicle.value = null;
});

watch(forceDeleteOpen, (value) => {
    if (!value && !restoreOpen.value) selectedVehicle.value = null;
});

function openRestore(vehicle: VehicleItem) {
    if (!canRestoreVehicle) return;
    selectedVehicle.value = vehicle;
    restoreOpen.value = true;
}

function openForceDelete(vehicle: VehicleItem) {
    if (!canForceDeleteVehicle) return;
    selectedVehicle.value = vehicle;
    forceDeleteOpen.value = true;
}

function confirmRestore() {
    if (!selectedVehicle.value) return;
    router.patch(
        `/vehicles/${selectedVehicle.value.id}/restore`,
        {},
        { onFinish: () => (restoreOpen.value = false) },
    );
}

function confirmForceDelete() {
    if (!selectedVehicle.value) return;
    router.delete(`/vehicles/${selectedVehicle.value.id}/force-delete`, {
        onFinish: () => (forceDeleteOpen.value = false),
    });
}

function humanize(text?: string | null) {
    if (!text) return '—';
    return text.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function vehicleTypeLabel(vehicle: VehicleItem) {
    if (typeof vehicle.vehicle_type === 'string') return humanize(vehicle.vehicle_type);
    if (vehicle.vehicle_type?.type_name) return humanize(vehicle.vehicle_type.type_name);
    return '—';
}
</script>

<template>
    <Head title="Archived Vehicles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Card class="mx-5">
                <CardHeader class="gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1.5">
                        <CardTitle class="flex items-center gap-2">
                            <Archive class="h-5 w-5 text-muted-foreground" />
                            Archived Vehicles
                        </CardTitle>
                        <CardDescription>
                            Review archived vehicles and restore or permanently delete them when needed.
                        </CardDescription>
                    </div>

                    <CardAction class="shrink-0">
                        <Button
                            as-child
                            size="sm"
                            variant="outline"
                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                        >
                            <Link :href="index().url">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Back to Vehicles
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="trash().url"
                                :initial-value="filters.search"
                                placeholder="Search archived vehicles…"
                                :only="['vehicles', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>

                        <div class="text-xs text-muted-foreground">
                            <span v-if="vehicles.total">
                                Showing {{ vehicles.from ?? 0 }}–{{ vehicles.to ?? 0 }} of
                                {{ vehicles.total }} archived vehicles
                            </span>
                            <span v-else>No archived vehicles found</span>
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
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Cap.</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Archived At</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Archived By</TableHead>
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
                                                <p class="text-sm font-semibold text-foreground">No archived vehicles</p>
                                                <p class="mt-0.5 text-xs text-muted-foreground">Nothing has been archived yet.</p>
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
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-blue-100">
                                                <Bus class="h-4 w-4 text-blue-700" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-medium">{{ vehicleTypeLabel(vehicle) }}</p>
                                                <p class="truncate text-xs text-muted-foreground">{{ vehicle.body_number || '—' }}</p>
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

                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ vehicle.deleted_at_human || '—' }}
                                    </TableCell>

                                    

                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ vehicle.deleter?.name || '—' }}
                                    </TableCell>

                                    <TableCell class="text-right">
                                        <DropdownMenu v-if="hasRowActions">
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

                                                <DropdownMenuSeparator v-if="canRestoreVehicle || canForceDeleteVehicle" />

                                                <DropdownMenuItem
                                                    v-if="canRestoreVehicle"
                                                    class="rounded-lg text-emerald-700 focus:bg-emerald-50 focus:text-emerald-700"
                                                    @click="openRestore(vehicle)"
                                                >
                                                    <RotateCcw class="mr-2 h-4 w-4" />
                                                    Restore Vehicle
                                                </DropdownMenuItem>

                                                <DropdownMenuSeparator v-if="canRestoreVehicle && canForceDeleteVehicle" />

                                                <DropdownMenuItem
                                                    v-if="canForceDeleteVehicle"
                                                    class="rounded-lg text-rose-600 focus:bg-rose-50 focus:text-rose-600"
                                                    @click="openForceDelete(vehicle)"
                                                >
                                                    <Trash2 class="mr-2 h-4 w-4" />
                                                    Delete Permanently
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>

                                        <span v-else class="text-xs text-muted-foreground">—</span>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <InertiaPagination
                        :links="vehicles.links"
                        :meta="{ from: vehicles.from, to: vehicles.to, total: vehicles.total }"
                    />
                </CardContent>
            </Card>
        </div>

        <!-- Restore Dialog -->
        <AlertDialog v-if="canRestoreVehicle" v-model:open="restoreOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle class="flex items-center gap-2">
                        <RotateCcw class="h-5 w-5 text-emerald-600" />
                        Restore Vehicle
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to restore
                        <span class="font-medium text-foreground">
                            {{ selectedVehicle?.plate_number || 'this vehicle' }}
                        </span>?
                        It will become active again and appear in the vehicles list.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-emerald-600 hover:bg-emerald-700 focus-visible:ring-emerald-500"
                        @click="confirmRestore"
                    >
                        Restore Vehicle
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Force Delete Dialog -->
        <AlertDialog v-if="canForceDeleteVehicle" v-model:open="forceDeleteOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle class="flex items-center gap-2">
                        <Trash2 class="h-5 w-5 text-rose-600" />
                        Delete Permanently
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to permanently delete
                        <span class="font-medium text-foreground">
                            {{ selectedVehicle?.plate_number || 'this vehicle' }}
                        </span>?
                        This action <span class="font-semibold text-rose-600">cannot be undone</span>
                        and all associated data will be lost forever.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-rose-600 hover:bg-rose-700 focus-visible:ring-rose-500"
                        @click="confirmForceDelete"
                    >
                        Delete Permanently
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
