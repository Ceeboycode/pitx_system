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
import { create, destroy, index, show, trash, edit } from '@/routes/routes';
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
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- {{ routes.links }} -->
            <Card>
                <CardHeader>
                    <CardTitle>Routes</CardTitle>
                    <CardDescription
                        >Manage and view all available routes in the
                        system.</CardDescription
                    >
                    <CardAction>
                        <Button size="sm" as-child variant="outline">
                            <Link :href="trash().url">View Trash</Link>
                        </Button>
                        <Button size="sm" as-child>
                            <Link :href="create().url">Create Route</Link>
                        </Button>
                    </CardAction>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableCaption>A list of routes</TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Route name</TableHead>
                                <TableHead>Gate name</TableHead>
                                <TableHead>Created At</TableHead>
                                <TableHead>Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="route in routes.data"
                                :key="route.id"
                            >
                                <TableCell>{{ route.route_name }}</TableCell>
                                <TableCell>{{
                                    route.gate?.gate_name
                                }}</TableCell>
                                <TableCell>
                                    {{ route.created_at_human || 'N/A' }}
                                </TableCell>
                                <TableCell>
                                    <Button
                                        as-child
                                        variant="outline"
                                        size="sm"
                                    >
                                        <Link :href="show(route.id).url">
                                            View
                                        </Link>
                                    </Button>
                                    <Button variant="secondary" size="sm"
                                        ><Link :href="edit(route.id).url">
                                            Edit
                                        </Link></Button
                                    >
                                    <Dialog>
                                        <DialogTrigger as-child>
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                @click="archivingId = route.id"
                                            >
                                                Archive
                                            </Button>
                                        </DialogTrigger>

                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle
                                                    >Archive Route</DialogTitle
                                                >
                                                <DialogDescription>
                                                    This route will be archived
                                                    and removed from the active
                                                    list. You can restore it
                                                    later from the trash.
                                                </DialogDescription>
                                            </DialogHeader>

                                            <DialogFooter class="gap-2">
                                                <DialogClose as-child>
                                                    <Button
                                                        variant="outline"
                                                        @click="
                                                            archivingId = null
                                                        "
                                                    >
                                                        Cancel
                                                    </Button>
                                                </DialogClose>

                                                <Button
                                                    variant="destructive"
                                                    @click="confirmArchive"
                                                >
                                                    Archive
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <InertiaPagination :links="routes.links" />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
