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
const selectedVehicle = ref<VehicleItem | null>(null);
const statusDialogOpen = ref(false);
const statusVehicle = ref<VehicleItem | null>(null);

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
    status === 'suspended' ? 'Unsuspend' : 'Suspend';

const openArchiveDialog = (vehicle: VehicleItem) => {
    selectedVehicle.value = vehicle;
    archiveDialogOpen.value = true;
};

const openStatusDialog = (vehicle: VehicleItem) => {
    statusVehicle.value = vehicle;
    statusDialogOpen.value = true;
};

const confirmToggleStatus = () => {
    if (!statusVehicle.value) return;

    router.patch(
        `/vehicles/${statusVehicle.value.id}/toggle-status`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                statusDialogOpen.value = false;
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
                            <Bus class="h-5 w-5 text-blue-700" />
                            Vehicles
                        </CardTitle>
                        <CardDescription class="mt-1">
                            List of all vehicles in the system.
                        </CardDescription>
                    </div>

                    <CardAction v-if="canViewArchived" class="flex items-center gap-2">
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
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Cap.</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Status</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Created</TableHead>
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
                                                    v-if="canToggleVehicleStatus"
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

        <AlertDialog v-model:open="statusDialogOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{ statusVehicle ? toggleLabel(statusVehicle.status) : 'Update Status' }}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        <template v-if="statusVehicle?.status === 'suspended'">
                            Unsuspending
                            <span class="font-semibold text-foreground">{{ statusVehicle?.plate_number || 'this vehicle' }}</span>
                            will set its status back to <span class="font-semibold text-foreground">Active</span>.
                        </template>
                        <template v-else>
                            Are you sure you want to suspend
                            <span class="font-semibold text-foreground">{{ statusVehicle?.plate_number || 'this vehicle' }}</span>?
                        </template>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg" @click="statusVehicle = null">Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        :class="[
                            'rounded-lg border-0 text-white',
                            statusVehicle?.status === 'suspended'
                                ? 'bg-emerald-600 hover:bg-emerald-700'
                                : 'bg-orange-500 hover:bg-orange-600',
                        ]"
                        @click="confirmToggleStatus"
                    >
                        {{ statusVehicle ? toggleLabel(statusVehicle.status) : 'Confirm' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
