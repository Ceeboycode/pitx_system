<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import { Button } from '@/components/ui/button';
import RestoreVehicleDialog from '@/components/vehicle/RestoreVehicleDialog.vue';
import ForceDeleteVehicleDialog from '@/components/vehicle/ForceDeleteVehicleDialog.vue';
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


import AppLayout from '@/layouts/AppLayout.vue';

import { index, trash } from '@/routes/vehicles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Download, Upload } from 'lucide-vue-next';

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
    {
        title: 'Vehicles',
        href: index().url,
    },
    {
        title: 'Archived Vehicles',
        href: trash().url,
    },
];
</script>

<template>
    <Head title="Archived Vehicles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card class="mx-5">
                <CardHeader>
                    <CardTitle>Archived Vehicles</CardTitle>
                    <CardDescription>
                        List of archived vehicles. You can restore or
                        permanently delete them.
                    </CardDescription>

                    <CardAction>
                        <Button as-child size="sm" variant="link" class="mr-2">
                            <Link :href="index().url">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Back to Vehicles
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
                                :route="trash().url"
                                :initial-value="filters.search"
                                placeholder="Search vehicles..."
                                :only="['vehicles', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>

                    </div>

                    <Table>
                        <!-- <TableCaption>Archived Vehicles</TableCaption> -->
                        <TableHeader>
                            <TableRow>
                                <TableHead>Company</TableHead>
                                <TableHead>Routes</TableHead>
                                <TableHead>Vehicle type</TableHead>
                                <TableHead>Plate number</TableHead>
                                <TableHead>Body number</TableHead>
                                <TableHead>Capacity</TableHead>
                                <TableHead>Archived At</TableHead>
                                <TableHead>Archived By</TableHead>
                                <TableHead class="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="vehicle in vehicles.data"
                                :key="vehicle.id"
                            >
                                <TableCell>{{
                                    vehicle.company?.company_name || '-'
                                }}</TableCell>
                                <TableCell>{{
                                    vehicle.route?.route_name || '-'
                                }}</TableCell>
                                <TableCell>{{
                                    vehicle.vehicle_type?.type_name || '-'
                                }}</TableCell>
                                <TableCell>{{
                                    vehicle.plate_number
                                }}</TableCell>
                                <TableCell>{{ vehicle.body_number }}</TableCell>
                                <TableCell>{{ vehicle.capacity }}</TableCell>
                                <TableCell>{{
                                    vehicle.deleted_at_human
                                }}</TableCell>
                                <TableCell>{{
                                    vehicle.deleter?.name || '-'
                                }}</TableCell>
                                <TableCell class="text-right">
                                    <RestoreVehicleDialog :vehicle="vehicle" />
                                    <ForceDeleteVehicleDialog
                                        :vehicle="vehicle"
                                    />
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="vehicles.data.length === 0">
                                <TableCell
                                    colspan="9"
                                    class="py-10 text-center text-muted-foreground"
                                >
                                    No archived vehicles found.
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
