<script setup lang="ts">
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, index } from '@/routes/vehicles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Button } from '@/components/ui/button'

defineProps<{
    vehicles: {
        data: any[];
        links: any[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Vehicles',
        href: index().url,
    },
];
</script>

<template>
    <Head title="Vehicles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card>
                <CardHeader>
                    <CardTitle>Vehicles</CardTitle>
                    <CardDescription>
                        List of all vehicles in the system.
                    </CardDescription>
                    <CardAction>
                        <Button size="sm" asChild>
                            <Link :href="create().url"> <Plus /> Add Vehicle</Link>
                        </Button>
                    </CardAction>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableCaption>List of Vehicles</TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Vehicle Type</TableHead>
                                <TableHead>Plate number</TableHead>
                                <TableHead>Company</TableHead>
                                <TableHead>Routes</TableHead>
                                <TableHead>Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="vehicle in vehicles.data"
                                :key="vehicle.id"
                            >
                                <TableCell>{{
                                    vehicle.vehicle_type?.type_name ?? '—'
                                }}</TableCell>
                                <TableCell>{{
                                    vehicle.plate_number
                                }}</TableCell>
                                <TableCell>{{
                                    vehicle.company?.company_name ?? '—'
                                }}</TableCell>
                                <TableCell>{{
                                    vehicle.route?.route_name ?? '—'
                                }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
