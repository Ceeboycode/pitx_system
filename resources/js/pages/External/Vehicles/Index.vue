<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import ExternalLayout from '@/layouts/ExternalLayout.vue';

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
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import CompanyVehicleController from '@/actions/App/Http/Controllers/CompanyVehicleController';
import {
    Bus,
    FileText,
    MoreHorizontal,
    Plus,
    Route as RouteIcon,
} from 'lucide-vue-next';

type Company = {
    id: number;
    company_name: string;
    company_code?: string | null;
    status: string;
    logo_url?: string | null;
};

type User = {
    id: number;
    name: string;
    username: string;
    email: string;
};

type VehicleRoute = {
    id: number;
    route_name: string;
};

type VehicleDocument = {
    id: number;
    vehicle_id: number;
    document_type: string;
    status: string;
};

type VehicleItem = {
    id: number;
    company_id: number;
    route_id?: number | null;
    vehicle_type: string;
    plate_number: string;
    body_number?: string | null;
    capacity?: number | null;
    color?: string | null;
    make_model?: string | null;
    status: string;
    created_at?: string | null;
    route?: VehicleRoute | null;
    documents?: VehicleDocument[];
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedVehicles = {
    data: VehicleItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};

const props = defineProps<{
    company: Company;
    user: User;
    vehicles: PaginatedVehicles;
    filters: {
        search?: string | null;
    };
}>();

function humanize(value?: string | null) {
    if (!value) return '—';

    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function formatDate(value?: string | null) {
    if (!value) return '—';

    return new Date(value).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function vehicleStatusVariant(status?: string | null) {
    if (status === 'active') return 'default';
    if (status === 'inactive') return 'secondary';
    return 'outline';
}

function documentStatusVariant(status?: string | null) {
    if (status === 'approved') return 'default';
    if (status === 'pending') return 'secondary';
    if (status === 'rejected') return 'destructive';
    return 'outline';
}

function documentsCount(documents?: VehicleDocument[]) {
    return documents?.length ?? 0;
}

function canToggleStatus(documents?: VehicleDocument[]) {
    if (!documents?.length) return false;

    return !documents.some(
        (doc) => doc.status === 'pending' || doc.status === 'rejected',
    );
}

function toggleLabel(status?: string | null) {
    return status === 'active' ? 'Set Inactive' : 'Set Active';
}

const totalVehicles = computed(() => props.vehicles.total ?? 0);

const statusDialog = reactive({
    open: false,
    vehicle: null as VehicleItem | null,
});

const statusConfirmOpen = computed({
    get: () => statusDialog.open,
    set: (value: boolean) => {
        statusDialog.open = value;
    },
});

function openStatusDialog(vehicle: VehicleItem) {
    statusDialog.vehicle = vehicle;
    statusDialog.open = true;
}

function confirmToggleStatus() {
    if (!statusDialog.vehicle) return;

    router.patch(
        CompanyVehicleController.toggleStatus(statusDialog.vehicle.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                statusDialog.open = false;
                statusDialog.vehicle = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Registered Vehicles" />

    <ExternalLayout :company="company" :user="user">
        <div class="space-y-6 p-4 md:p-6">
            <div
                class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Registered Vehicles
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        View and manage your registered vehicles.
                    </p>
                </div>

                <Button as-child>
                    <Link :href="CompanyVehicleController.create().url">
                        <Plus class="mr-2 h-4 w-4" />
                        Register Vehicle
                    </Link>
                </Button>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Total Vehicles</CardDescription>
                        <CardTitle class="text-2xl">
                            {{ totalVehicles }}
                        </CardTitle>
                    </CardHeader>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Company</CardDescription>
                        <CardTitle class="text-base">
                            {{ company.company_code ?? company.company_name }}
                        </CardTitle>
                    </CardHeader>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Current Page</CardDescription>
                        <CardTitle class="text-base">
                            {{ vehicles.current_page }} /
                            {{ vehicles.last_page }}
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <Card>
                <CardHeader class="space-y-4">
                    <div>
                        <CardTitle>Vehicle List</CardTitle>
                        <CardDescription>
                            Search by plate number, vehicle type, body number,
                            or model.
                        </CardDescription>
                    </div>

                    <SearchInput
                        :route="CompanyVehicleController.index().url"
                        :initial-value="filters.search"
                        placeholder="Search vehicles..."
                        :only="['vehicles', 'filters']"
                    />
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="overflow-x-auto rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Plate Number</TableHead>
                                    <TableHead>Vehicle Info</TableHead>
                                    <TableHead>Route</TableHead>
                                    <TableHead>Documents</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Created</TableHead>
                                    <TableHead class="text-right"
                                        >Action</TableHead
                                    >
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow v-if="vehicles.data.length === 0">
                                    <TableCell
                                        colspan="7"
                                        class="py-10 text-center text-sm text-muted-foreground"
                                    >
                                        No vehicles found.
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="vehicle in vehicles.data"
                                    :key="vehicle.id"
                                >
                                    <TableCell class="font-medium">
                                        {{ vehicle.plate_number }}
                                    </TableCell>

                                    <TableCell>
                                        <div class="space-y-1">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <Bus
                                                    class="h-4 w-4 text-muted-foreground"
                                                />
                                                <span>{{
                                                    vehicle.vehicle_type
                                                }}</span>
                                            </div>

                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                Body No:
                                                {{ vehicle.body_number || '—' }}
                                            </p>

                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{ vehicle.make_model || '—' }}
                                            </p>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <div class="space-y-1">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <RouteIcon
                                                    class="h-4 w-4 text-muted-foreground"
                                                />
                                                <span>{{
                                                    vehicle.route?.route_name ||
                                                    'No route assigned'
                                                }}</span>
                                            </div>

                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                Capacity:
                                                {{ vehicle.capacity ?? '—' }}
                                            </p>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <Popover
                                            v-if="vehicle.documents?.length"
                                        >
                                            <PopoverTrigger as-child>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                >
                                                    <FileText
                                                        class="mr-2 h-4 w-4"
                                                    />
                                                    {{
                                                        documentsCount(
                                                            vehicle.documents,
                                                        )
                                                    }}
                                                    document{{
                                                        documentsCount(
                                                            vehicle.documents,
                                                        ) > 1
                                                            ? 's'
                                                            : ''
                                                    }}
                                                </Button>
                                            </PopoverTrigger>

                                            <PopoverContent class="w-80">
                                                <div class="space-y-3">
                                                    <div>
                                                        <h4
                                                            class="text-sm font-medium"
                                                        >
                                                            Document Statuses
                                                        </h4>
                                                        <p
                                                            class="text-xs text-muted-foreground"
                                                        >
                                                            Backend document
                                                            statuses for this
                                                            vehicle.
                                                        </p>
                                                    </div>

                                                    <div class="space-y-2">
                                                        <div
                                                            v-for="doc in vehicle.documents"
                                                            :key="doc.id"
                                                            class="flex items-center justify-between gap-3 rounded-md border p-3"
                                                        >
                                                            <div
                                                                class="min-w-0"
                                                            >
                                                                <p
                                                                    class="truncate text-sm font-medium"
                                                                >
                                                                    {{
                                                                        humanize(
                                                                            doc.document_type,
                                                                        )
                                                                    }}
                                                                </p>
                                                            </div>

                                                            <Badge
                                                                :variant="
                                                                    documentStatusVariant(
                                                                        doc.status,
                                                                    )
                                                                "
                                                            >
                                                                {{
                                                                    humanize(
                                                                        doc.status,
                                                                    )
                                                                }}
                                                            </Badge>
                                                        </div>
                                                    </div>
                                                </div>
                                            </PopoverContent>
                                        </Popover>

                                        <div
                                            v-else
                                            class="flex items-center gap-2 text-xs text-muted-foreground"
                                        >
                                            <FileText class="h-3.5 w-3.5" />
                                            <span>No documents uploaded</span>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <Badge
                                            :variant="
                                                vehicleStatusVariant(
                                                    vehicle.status,
                                                )
                                            "
                                        >
                                            {{ humanize(vehicle.status) }}
                                        </Badge>
                                    </TableCell>

                                    <TableCell
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{ formatDate(vehicle.created_at) }}
                                    </TableCell>

                                    <TableCell class="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                >
                                                    <MoreHorizontal
                                                        class="h-4 w-4"
                                                    />
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent
                                                align="end"
                                                class="w-44"
                                            >
                                                <DropdownMenuLabel>
                                                    Actions
                                                </DropdownMenuLabel>

                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem as-child>
                                                    <Link
                                                        :href="
                                                            CompanyVehicleController.show(
                                                                vehicle.id,
                                                            ).url
                                                        "
                                                    >
                                                        View
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem as-child>
                                                    <Link
                                                        :href="`/company/vehicles/${vehicle.id}/edit`"
                                                    >
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="
                                                        canToggleStatus(
                                                            vehicle.documents,
                                                        )
                                                    "
                                                    @click="
                                                        openStatusDialog(
                                                            vehicle,
                                                        )
                                                    "
                                                >
                                                    {{
                                                        toggleLabel(
                                                            vehicle.status,
                                                        )
                                                    }}
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-else
                                                    disabled
                                                    class="text-muted-foreground"
                                                >
                                                    {{
                                                        toggleLabel(
                                                            vehicle.status,
                                                        )
                                                    }}
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <InertiaPagination
                        v-if="vehicles.last_page > 1"
                        :links="vehicles.links"
                        :meta="{
                            from: vehicles.from,
                            to: vehicles.to,
                            total: vehicles.total,
                        }"
                    />
                </CardContent>
            </Card>

            <AlertDialog v-model:open="statusConfirmOpen">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {{
                                statusDialog.vehicle
                                    ? toggleLabel(statusDialog.vehicle.status)
                                    : 'Update Status'
                            }}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            This will update the status of
                            <span class="font-medium text-foreground">
                                {{
                                    statusDialog.vehicle?.plate_number ||
                                    'selected vehicle'
                                }}
                            </span>
                            .
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    <AlertDialogFooter>
                        <AlertDialogCancel @click="statusDialog.vehicle = null">
                            Cancel
                        </AlertDialogCancel>
                        <AlertDialogAction @click="confirmToggleStatus">
                            Confirm
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </ExternalLayout>
</template>
