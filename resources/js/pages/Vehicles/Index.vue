<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';

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
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Archive,
    Download,
    MoreHorizontal,
    Plus,
    Upload,
} from 'lucide-vue-next';
import { ref } from 'vue';

import { create, destroy, edit, index, show, trash } from '@/routes/vehicles';
import { type BreadcrumbItem } from '@/types';

type VehicleItem = {
    id: number;
    status?: string | null;
    vehicle_type?: string | null;
    plate_number?: string | null;
    body_number?: string | null;
    capacity?: string | number | null;
    created_at?: string | null;
    company?: {
        company_name?: string | null;
    } | null;
    route?: {
        route_name?: string | null;
    } | null;
};

const props = defineProps<{
    vehicles: {
        data: VehicleItem[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: {
        search: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vehicles', href: index().url },
];

const archiveDialogOpen = ref(false);
const selectedVehicle = ref<VehicleItem | null>(null);
const statusDialogOpen = ref(false);
const statusVehicle = ref<VehicleItem | null>(null);

const formatDate = (value?: string | null) => {
    if (!value) return '-';

    return new Date(value).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const statusVariant = (status?: string | null) => {
    switch (status) {
        case 'active':
        case 'verified':
            return 'default' as const;
        case 'draft':
        case 'pending':
        case 'for_verification':
            return 'secondary' as const;
        case 'invalid':
        case 'inactive':
        case 'needs_revision':
            return 'destructive' as const;
        default:
            return 'outline' as const;
    }
};

const humanize = (text?: string | null) => {
    if (!text) return 'N/A';

    return text.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
};

const openArchiveDialog = (vehicle: VehicleItem) => {
    selectedVehicle.value = vehicle;
    archiveDialogOpen.value = true;
};

const openStatusDialog = (vehicle: VehicleItem) => {
    statusVehicle.value = vehicle;
    statusDialogOpen.value = true;
};

const toggleLabel = (status?: string | null) =>
    status === 'active' ? 'Set Inactive' : 'Set Active';

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
    router.delete(
        destroy({
            vehicle: vehicle.id,
        }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                archiveDialogOpen.value = false;
                selectedVehicle.value = null;
            },
        },
    );
};
</script>

<template>
    <Head title="Vehicles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card class="mx-5">
                <CardHeader>
                    <CardTitle>Vehicles</CardTitle>
                    <CardDescription
                        >List of all vehicles in the system.</CardDescription
                    >

                    <CardAction class="flex flex-wrap gap-2">
                        <Button as-child size="sm" variant="outline">
                            <Link :href="trash().url">
                                <Archive class="mr-2 h-4 w-4" />
                                View Archived
                            </Link>
                        </Button>

                        <Button as-child size="sm">
                            <Link :href="create().url">
                                <Plus class="mr-2 h-4 w-4" />
                                Add Vehicle
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="index().url"
                                :initial-value="filters.search"
                                placeholder="Search vehicles..."
                                :only="['vehicles', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>

                        <div class="flex gap-2 sm:justify-end">
                            <Button
                                class="cursor-pointer"
                                size="sm"
                                variant="outline"
                            >
                                <Upload class="mr-2 h-4 w-4" />
                                Import
                            </Button>

                            <Button
                                class="cursor-pointer"
                                size="sm"
                                variant="outline"
                            >
                                <Download class="mr-2 h-4 w-4" />
                                Export
                            </Button>
                        </div>
                    </div>

                    <Table>
                        <!-- <TableCaption>List of vehicles.</TableCaption> -->

                        <TableHeader>
                            <TableRow>
                                <TableHead>Company</TableHead>
                                <TableHead>Route</TableHead>
                                <TableHead>Vehicle Type</TableHead>
                                <TableHead>Plate Number</TableHead>
                                <TableHead>Body Number</TableHead>
                                <TableHead>Capacity</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Created At</TableHead>
                                <TableHead class="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="vehicle in vehicles.data"
                                :key="vehicle.id"
                            >
                                <TableCell>
                                    {{ vehicle.company?.company_name || 'N/A' }}
                                </TableCell>

                                <TableCell>
                                    {{ vehicle.route?.route_name || 'N/A' }}
                                </TableCell>

                                <TableCell>
                                    {{ vehicle.vehicle_type || 'N/A' }}
                                </TableCell>

                                <TableCell>
                                    {{ vehicle.plate_number || 'N/A' }}
                                </TableCell>

                                <TableCell>
                                    {{ vehicle.body_number || 'N/A' }}
                                </TableCell>

                                <TableCell>
                                    {{ vehicle.capacity || 'N/A' }}
                                </TableCell>

                                <TableCell>
                                    <Badge
                                        :variant="statusVariant(vehicle.status)"
                                    >
                                        {{ humanize(vehicle.status) }}
                                    </Badge>
                                </TableCell>

                                <TableCell>
                                    {{ formatDate(vehicle.created_at) }}
                                </TableCell>

                                <TableCell class="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                class="h-8 w-8 p-0"
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
                                            class="w-40"
                                        >
                                            <DropdownMenuLabel
                                                >Actions</DropdownMenuLabel
                                            >
                                            <DropdownMenuSeparator />

                                            <DropdownMenuItem as-child>
                                                <Link
                                                    :href="
                                                        show({
                                                            vehicle: vehicle.id,
                                                        }).url
                                                    "
                                                >
                                                    Review
                                                </Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem as-child>
                                                <Link
                                                    :href="
                                                        edit({
                                                            vehicle: vehicle.id,
                                                        }).url
                                                    "
                                                >
                                                    Edit
                                                </Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                @click="
                                                    openStatusDialog(vehicle)
                                                "
                                            >
                                                {{
                                                    toggleLabel(vehicle.status)
                                                }}
                                            </DropdownMenuItem>

                                            <DropdownMenuSeparator />

                                            <DropdownMenuItem
                                                class="text-destructive focus:text-destructive"
                                                @click="
                                                    openArchiveDialog(vehicle)
                                                "
                                            >
                                                Archive
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="vehicles.data.length === 0">
                                <TableCell
                                    colspan="9"
                                    class="py-10 text-center text-muted-foreground"
                                >
                                    No vehicles found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

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
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Archive Vehicle</AlertDialogTitle>
                    <AlertDialogDescription>
                        You are about to archive
                        <span class="font-medium text-foreground">
                            {{
                                selectedVehicle?.plate_number || 'this vehicle'
                            }}
                        </span>
                        . You can restore it later from Archived Vehicles.
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <AlertDialogFooter>
                    <AlertDialogCancel @click="selectedVehicle = null">
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        @click="
                            selectedVehicle && archiveVehicle(selectedVehicle)
                        "
                    >
                        Archive
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog v-model:open="statusDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{
                            statusVehicle
                                ? toggleLabel(statusVehicle.status)
                                : 'Update Status'
                        }}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        Update status for
                        <span class="font-medium text-foreground">
                            {{ statusVehicle?.plate_number || 'this vehicle' }}
                        </span>
                        ?
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <AlertDialogFooter>
                    <AlertDialogCancel @click="statusVehicle = null">
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction @click="confirmToggleStatus">
                        Confirm
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
