<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';

import RestoreVehicleDialog from '@/components/vehicle/RestoreVehicleDialog.vue';

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
const restoreOpen = ref(false);
const selectedVehicle = ref<any | null>(null);

function openRestore(vehicle: any) {
    selectedVehicle.value = vehicle;
    restoreOpen.value = true;
}

function humanize(text?: string | null) {
    if (!text) return '—';
    return text.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
</script>

<template>
    <Head title="Archived Vehicles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card >
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <!-- <span>Change Requests</span> -->
                        <!-- TODO: make the text straight, not wrapped -->
                        <Button
                            as-child
                            variant="outline"
                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100 mr-2"
                        >
                            <Link :href="index().url">
                                <ArrowLeft class="h-4 w-4" />
                            </Link>
                        </Button>
                        Archives
                        <span class="ml-2 flex flex-1 items-center">
                            <hr class="h-px w-full border border-rose-500" />
                            <div class="border-7 border-rose-500 rounded-xs">
                                <div class="border-3 border-white rounded-xs"></div>
                            </div>
                        </span>
                    </CardTitle>
                    <CardDescription class="mt-1">
                        List of archived vehicles. You can restore them anytime.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
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
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader class="border-y border-slate-200">
                                <TableRow class="gap-2">
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Company</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Route</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Vehicle Info</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Plate Number</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Cap.</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Archived At</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Archived By</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-right text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Actions</TableHead
                                    >
                                </TableRow>
                            </TableHeader>

                            <TableBody class="border-y border-slate-200">
                                <!-- Empty state -->
                                <TableRow
                                    v-if="vehicles.data.length === 0"
                                    class="hover:bg-transparent"
                                >
                                    <TableCell
                                        colspan="8"
                                        class="py-20 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center gap-3"
                                        >
                                            <div
                                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted"
                                            >
                                                <Bus
                                                    class="h-6 w-6 text-muted-foreground/40"
                                                />
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-foreground"
                                                >
                                                    No archived vehicles
                                                </p>
                                                <p
                                                    class="mt-0.5 text-xs text-muted-foreground"
                                                >
                                                    Nothing has been archived
                                                    yet.
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="vehicle in vehicles.data"
                                    :key="vehicle.id"
                                    class="group transition-colors hover:bg-muted/30"
                                >
                                    <!-- Company -->
                                    <TableCell class="px-0 text-sm font-medium">
                                        {{
                                            vehicle.company?.company_name || '—'
                                        }}
                                    </TableCell>

                                    <!-- Route -->
                                    <TableCell class="px-0">
                                        <div
                                            v-if="vehicle.route?.route_name"
                                            class="flex items-center gap-1.5"
                                        >
                                            <RouteIcon
                                                class="h-3.5 w-3.5 shrink-0 text-sky-600"
                                            />
                                            <span class="text-sm">{{
                                                vehicle.route.route_name
                                            }}</span>
                                        </div>
                                        <span
                                            v-else
                                            class="text-sm text-muted-foreground"
                                            >—</span
                                        >
                                    </TableCell>

                                    <!-- Vehicle Info -->
                                    <TableCell class="px-0">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-blue-100"
                                            >
                                                <Bus
                                                    class="h-3.5 w-3.5 text-blue-700"
                                                />
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium">
                                                    {{
                                                        humanize(
                                                            vehicle.vehicle_type
                                                                ?.type_name ??
                                                                vehicle.vehicle_type,
                                                        )
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    {{
                                                        vehicle.body_number ||
                                                        '—'
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Plate Number -->
                                    <TableCell class="px-0">
                                        <span
                                            class="rounded bg-muted px-2 py-0.5 font-mono text-xs font-semibold"
                                        >
                                            {{ vehicle.plate_number || '—' }}
                                        </span>
                                    </TableCell>

                                    <!-- Capacity -->
                                    <TableCell
                                        class="px-0 text-sm text-muted-foreground tabular-nums"
                                    >
                                        {{ vehicle.capacity || '—' }}
                                    </TableCell>

                                    <!-- Archived At -->
                                    <TableCell
                                        class="px-0 text-sm text-muted-foreground"
                                    >
                                        {{ vehicle.deleted_at_human || '—' }}
                                    </TableCell>

                                    <!-- Archived By -->
                                    <TableCell
                                        class="px-0 text-sm text-muted-foreground"
                                    >
                                        {{ vehicle.deleter?.name || '—' }}
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="text-right px-0">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="outline"
                                                    class="rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground cursor-pointer"
                                                >
                                                    <MoreHorizontal
                                                        class="h-4 w-4"
                                                    />
                                                    <span class="sr-only"
                                                        >Open actions</span
                                                    >
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent
                                                align="end"
                                                class="w-fit rounded-lg border-slate-200 shadow-lg"
                                            >
                                                <DropdownMenuLabel
                                                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                                                >
                                                    {{
                                                        vehicle.plate_number ||
                                                        'Vehicle'
                                                    }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    class="rounded-lg cursor-pointer hover:bg-slate-100"
                                                    @click="
                                                        openRestore(vehicle)
                                                    "
                                                >
                                                    <RotateCcw
                                                        class="h-4 w-4"
                                                    />
                                                    Restore Vehicle
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
    </AppLayout>
</template>
