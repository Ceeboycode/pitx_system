<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
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

import AppLayout from '@/layouts/AppLayout.vue';
import { create, destroy, edit, index, show, trash } from '@/routes/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import {
    Archive,
    ArchiveIcon,
    Download,
    Edit as EditIcon,
    Eye,
    Plus,
    Upload,
} from 'lucide-vue-next';

interface Gate {
    id: number;
    gate_name: string;
}

interface RouteRow {
    id: number;
    route_name: string;
    created_at_human: string | null;
    gate: Gate | null;
}

const props = withDefaults(
    defineProps<{
        routes: any; // paginator
        filters?: { search: string | null };
    }>(),
    {
        filters: () => ({ search: null }),
    },
);

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
            <Card class="mx-10 mt-3">
                <CardHeader>
                    <CardTitle>Routes</CardTitle>
                    <CardDescription>
                        Manage and view all available routes in the system.
                    </CardDescription>

                    <CardAction>
                        <Button
                            as-child
                            size="sm"
                            variant="outline"
                            class="mr-2"
                        >
                            <Link :href="trash().url" class="cursor-pointer">
                                <Archive class="mr-2 h-4 w-4" />
                                View Archived
                            </Link>
                        </Button>

                        <Button as-child size="sm">
                            <Link :href="create().url" class="cursor-pointer">
                                <Plus class="mr-2 h-4 w-4" />
                                Create Route
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <!-- Search + Import/Export -->
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="index().url"
                                :initial-value="props.filters.search"
                                placeholder="Search routes..."
                                :only="['routes', 'filters']"
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

                    <!-- Table -->
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Route Name</TableHead>
                                <TableHead>Gate Name</TableHead>
                                <TableHead>Created At</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="routeItem in (props.routes.data as RouteRow[])"
                                :key="routeItem.id"
                            >
                                <TableCell class="capitalize">
                                    {{ routeItem.route_name }}
                                </TableCell>

                                <TableCell>
                                    {{ routeItem.gate?.gate_name ?? 'N/A' }}
                                </TableCell>

                                <TableCell>
                                    {{ routeItem.created_at_human || 'N/A' }}
                                </TableCell>

                                <TableCell class="space-x-2">
                                    <Button as-child size="sm" variant="ghost">
                                        <Link
                                            :href="show(routeItem.id).url"
                                            class="cursor-pointer"
                                        >
                                            <Eye class="mr-2 h-4 w-4" />
                                            View
                                        </Link>
                                    </Button>

                                    <Button as-child size="sm" variant="default">
                                        <Link
                                            :href="edit(routeItem.id).url"
                                            class="cursor-pointer"
                                        >
                                            <EditIcon class="mr-2 h-4 w-4" />
                                            Edit
                                        </Link>
                                    </Button>

                                    <Dialog>
                                        <DialogTrigger as-child>
                                            <Button
                                                class="cursor-pointer"
                                                size="sm"
                                                variant="archive"
                                                @click="
                                                    archivingId = routeItem.id
                                                "
                                            >
                                                <ArchiveIcon class="mr-2 h-4 w-4" />
                                                Archive
                                            </Button>
                                        </DialogTrigger>

                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle
                                                    class="flex items-center gap-2"
                                                >
                                                    <Archive
                                                        :size="18"
                                                        class="text-muted-foreground"
                                                    />
                                                    Archive Route
                                                </DialogTitle>
                                                <DialogDescription>
                                                    Are you sure you want to
                                                    archive this route? You can
                                                    restore it later from the
                                                    Trash.
                                                </DialogDescription>
                                            </DialogHeader>

                                            <DialogFooter class="gap-2">
                                                <DialogClose as-child>
                                                    <Button
                                                        class="cursor-pointer"
                                                        variant="outline"
                                                        @click="archivingId = null"
                                                    >
                                                        Cancel
                                                    </Button>
                                                </DialogClose>

                                                <DialogClose as-child>
                                                    <Button
                                                        class="cursor-pointer"
                                                        variant="archive"
                                                        @click="confirmArchive"
                                                    >
                                                        <ArchiveIcon class="mr-2 h-4 w-4" />
                                                        Archive
                                                    </Button>
                                                </DialogClose>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                </TableCell>
                            </TableRow>

                            <!-- Empty State -->
                            <TableRow v-if="props.routes.data.length === 0">
                                <TableCell
                                    colspan="4"
                                    class="py-10 text-center text-muted-foreground"
                                >
                                    No routes found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Pagination (Companies/Gates placement) -->
                    <InertiaPagination
                        :links="props.routes.links"
                        :meta="{
                            from: props.routes.from,
                            to: props.routes.to,
                            total: props.routes.total,
                        }"
                    />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
