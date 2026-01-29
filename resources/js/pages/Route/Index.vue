<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
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
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, destroy, index, show, edit, trash } from '@/routes/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Gate {
    id: number;
    gate_name: string;
}

interface Route {
    id: number;
    route_name: string;
    created_at_human: string | null;
    gate: Gate | null;
}

defineProps<{
    routes: {
        data: Route[];
        links: [];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Routes',
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
    <Head title="Routes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card>
                <CardHeader>
                    <CardTitle>Routes</CardTitle>
                    <CardDescription>Manage and view all available routes in the system.</CardDescription>

                    <CardAction class="flex gap-2">
                        <Button size="sm" asChild variant="outline">
                            <Link :href="trash().url">View Trash</Link>
                        </Button>
                        <Button size="sm" asChild>
                            <Link :href="create().url">Create Route</Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Route Name</TableHead>
                                <TableHead>Gate Name</TableHead>
                                <TableHead>Created At</TableHead>
                                <TableHead>Action</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-for="route in routes.data" :key="route.id">
                                <TableCell>{{ route.route_name }}</TableCell>
                                <TableCell>{{ route.gate?.gate_name ?? 'N/A' }}</TableCell>
                                <TableCell>{{ route.created_at_human || 'N/A' }}</TableCell>

                                <TableCell class="space-x-2">
                                    <Button asChild size="sm" variant="outline">
                                        <Link :href="show(route.id).url">View</Link>
                                    </Button>
                                    <Button size="sm" asChild variant="default">
                                        <Link :href="edit(route.id).url">Edit</Link>
                                    </Button>

                                    <Dialog>
                                        <DialogTrigger asChild>
                                            <Button size="sm" variant="archive" @click="archivingId = route.id">
                                                Archive
                                            </Button>
                                        </DialogTrigger>

                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>Archive Route</DialogTitle>
                                                <DialogDescription>
                                                    This route will be archived and removed from the active list. You can restore it later from the trash.
                                                </DialogDescription>
                                            </DialogHeader>

                                            <DialogFooter class="gap-2">
                                                <DialogClose asChild>
                                                    <Button variant="outline" @click="archivingId = null">
                                                        Cancel
                                                    </Button>
                                                </DialogClose>
                                                <Button variant="archive" @click="confirmArchive">
                                                    Archive
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <InertiaPagination :links="routes.links" class="mt-4" />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
