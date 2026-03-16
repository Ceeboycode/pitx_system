<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';

import RestoreVehicleDialog from '@/components/vehicle/RestoreVehicleDialog.vue';
import ForceDeleteVehicleDialog from '@/components/vehicle/ForceDeleteVehicleDialog.vue';

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

import AppLayout from '@/layouts/AppLayout.vue';
import { index, trash } from '@/routes/vehicles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

import {
    Archive,
    ArrowLeft,
    Bus,
    MoreHorizontal,
    RotateCcw,
    Route as RouteIcon,
    Trash2,
} from 'lucide-vue-next';

import { ref } from 'vue';

const props = defineProps<{
    vehicles: {
        data: any[];
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

// We delegate to the dialog components — track which vehicle's dialogs are open
const restoreOpen      = ref(false);
const forceDeleteOpen  = ref(false);
const selectedVehicle  = ref<any | null>(null);

function openRestore(vehicle: any) {
    selectedVehicle.value = vehicle;
    restoreOpen.value = true;
}

function openForceDelete(vehicle: any) {
    selectedVehicle.value = vehicle;
    forceDeleteOpen.value = true;
}

function humanize(text?: string | null) {
    if (!text) return '—';
    return text.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
</script>

<template>
    <Head title="Archived Vehicles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-5">
                <CardHeader>
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <Archive class="h-5 w-5 text-muted-foreground" />
                            Archived Vehicles
                        </CardTitle>
                        <CardDescription class="mt-1">
                            List of archived vehicles. You can restore or permanently delete them.
                        </CardDescription>
                    </div>

                    <CardAction>
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

                    <!-- Search -->
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="trash().url"
                                :initial-value="filters.search"
                                placeholder="Search archived vehicles…"
                                :only="['vehicles', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>
                    </div>

                    <!-- Table -->
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
                                <!-- Empty state -->
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
                                    <!-- Company -->
                                    <TableCell class="text-sm font-medium">
                                        {{ vehicle.company?.company_name || '—' }}
                                    </TableCell>

                                    <!-- Route -->
                                    <TableCell>
                                        <div v-if="vehicle.route?.route_name" class="flex items-center gap-1.5">
                                            <RouteIcon class="h-3.5 w-3.5 shrink-0 text-sky-600" />
                                            <span class="text-sm">{{ vehicle.route.route_name }}</span>
                                        </div>
                                        <span v-else class="text-sm text-muted-foreground">—</span>
                                    </TableCell>

                                    <!-- Vehicle Info -->
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-blue-100">
                                                <Bus class="h-3.5 w-3.5 text-blue-700" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium">
                                                    {{ humanize(vehicle.vehicle_type?.type_name ?? vehicle.vehicle_type) }}
                                                </p>
                                                <p class="text-xs text-muted-foreground">{{ vehicle.body_number || '—' }}</p>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Plate Number -->
                                    <TableCell>
                                        <span class="rounded bg-muted px-2 py-0.5 font-mono text-xs font-semibold">
                                            {{ vehicle.plate_number || '—' }}
                                        </span>
                                    </TableCell>

                                    <!-- Capacity -->
                                    <TableCell class="text-sm text-muted-foreground tabular-nums">
                                        {{ vehicle.capacity || '—' }}
                                    </TableCell>

                                    <!-- Archived At -->
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ vehicle.deleted_at_human || '—' }}
                                    </TableCell>

                                    <!-- Archived By -->
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ vehicle.deleter?.name || '—' }}
                                    </TableCell>

                                    <!-- Actions -->
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
                                                    class="rounded-lg text-emerald-700 focus:bg-emerald-50 focus:text-emerald-700"
                                                    @click="openRestore(vehicle)"
                                                >
                                                    <RotateCcw class="mr-2 h-4 w-4" />
                                                    Restore Vehicle
                                                </DropdownMenuItem>

                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    class="rounded-lg text-rose-600 focus:bg-rose-50 focus:text-rose-600"
                                                    @click="openForceDelete(vehicle)"
                                                >
                                                    <Trash2 class="mr-2 h-4 w-4" />
                                                    Delete Permanently
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

        <!-- Dialogs -->
        <RestoreVehicleDialog
            v-if="selectedVehicle"
            v-model:open="restoreOpen"
            :vehicle="selectedVehicle"
        />

        <ForceDeleteVehicleDialog
            v-if="selectedVehicle"
            v-model:open="forceDeleteOpen"
            :vehicle="selectedVehicle"
        />
    </AppLayout>
</template>