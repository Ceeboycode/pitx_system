<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import AppLayout from '@/layouts/AppLayout.vue';
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
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index, create, destroy, show, edit, trash } from '@/routes/route-stops';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface RouteStop {
    id: number;
    stop_name: string;
    route_name: string | null;
    order: number;
    created_at: string | null;
}

defineProps<{
    routeStops: {
        data: RouteStop[];
        links: any[];
        meta: any;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Route Stops',
        href: index().url,
    },
];

const archivingId = ref<number | null>(null);

const confirmArchive = () => {
    if (!archivingId.value) return;

    router.delete(destroy(archivingId.value).url, {
        preserveScroll: true,
        onFinish: () => {
            archivingId.value = null;
        },
    });
};
</script>

<template>
    <Head title="Route Stops" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-10 mt-3">
                <CardHeader>
                    <CardTitle>Route Stops</CardTitle>
                    <CardDescription>Manage and view all route stops in the system.</CardDescription>
                </CardHeader>

                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Route Name</TableHead>
                                <TableHead>Stop Name</TableHead>
                                <TableHead>Order</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-for="stop in routeStops.data" :key="stop.id">
                                <TableCell>{{ stop.route_name || 'N/A' }}</TableCell>
                                <TableCell>{{ stop.stop_name }}</TableCell>
                                <TableCell>{{ stop.order }}</TableCell>
                            </TableRow>
                            <TableRow v-if="routeStops.data.length === 0">
                                <TableCell
                                    colspan="3"
                                    class="py-10 text-center text-muted-foreground"
                                >
                                    No route stops found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- <InertiaPagination :links="routeStops.links" class="mt-4" /> -->

                    <InertiaPagination
                        :links="routeStops.links"
                        :meta="{
                            from: routeStops.from,
                            to: routeStops.to,
                            total: routeStops.total,
                        }"
                    />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
