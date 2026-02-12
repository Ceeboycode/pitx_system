<script setup lang="ts">
import SearchInput from '@/components/SearchInput.vue';
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
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import ArchiveVehicleDialog from '@/components/vehicle/ArchiveVehicleDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Archive, Download, Edit, Eye, Plus, Upload } from 'lucide-vue-next';

import InertiaPagination from '@/components/InertiaPagination.vue';
import { create, edit, index } from '@/routes/vehicles';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    vehicles: {
        data: any[];
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

                    <CardAction>
                        <Button
                            as-child
                            size="sm"
                            variant="outline"
                            class="mr-2"
                        >
                            <Link :href="index().url">
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
                        <TableCaption>List of vehicles.</TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Company</TableHead>
                                <TableHead>Routes</TableHead>
                                <TableHead>Vehicle type</TableHead>
                                <TableHead>Plate number</TableHead>
                                <TableHead>Body number</TableHead>
                                <TableHead>Capacity</TableHead>
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
                                    {{
                                        vehicle.vehicle_type?.type_name || 'N/A'
                                    }}
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
                                    {{ vehicle.created_at_human }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <div
                                        class="flex flex-wrap justify-end gap-2"
                                    >
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="cursor-pointer"
                                        >
                                            <Eye class="mr-2 h-4 w-4" />
                                            View
                                        </Button>

                                        <Button
                                            variant="default"
                                            size="sm"
                                            as-child
                                        >
                                            <Link
                                                :href="
                                                    edit({
                                                        vehicle: vehicle.id,
                                                    }).url
                                                "
                                            >
                                                <Edit class="mr-2 h-4 w-4" />
                                                Edit
                                            </Link>
                                        </Button>

                                        <ArchiveVehicleDialog
                                            :vehicle="vehicle"
                                        />
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="vehicles.data.length === 0">
                                <TableCell
                                    colspan="7"
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
    </AppLayout>
</template>
