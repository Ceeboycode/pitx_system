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
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
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
import {
    forceDelete,
    index,
    restore,
    trash,
} from '@/actions/App/Http/Controllers/RouteController';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import {
    ArrowLeft,
    MoreHorizontal,
    RotateCcw,
    Trash2,
} from 'lucide-vue-next';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Gate {
    id: number;
    gate_name: string;
}

interface RouteRow {
    id: number;
    route_name: string;
    status: 'active' | 'inactive';
    deleted_at_human: string | null;
    gate: Gate | null;
}

// ─── Props ────────────────────────────────────────────────────────────────────

const props = withDefaults(
    defineProps<{
        routes: any;
        filters?: { search: string | null };
    }>(),
    { filters: () => ({ search: null }) },
);

// ─── Breadcrumbs ──────────────────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Routes', href: index().url },
    { title: 'Archived', href: trash().url },
];

// ─── Restore ─────────────────────────────────────────────────────────────────

function handleRestore(id: number) {
    router.patch(restore(id).url, {}, { preserveScroll: true });
}

// ─── Force delete dialog ──────────────────────────────────────────────────────

const deletingId   = ref<number | null>(null);
const deleteOpen   = ref(false);

function openDeleteDialog(id: number) {
    deletingId.value = id;
    deleteOpen.value = true;
}

function confirmForceDelete() {
    if (!deletingId.value) return;
    router.delete(forceDelete(deletingId.value).url, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
            deleteOpen.value  = false;
        },
    });
}
</script>

<template>
    <Head title="Archived Routes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-10 mt-3">
                <CardHeader>
                    <CardTitle>Archived Routes</CardTitle>
                    <CardDescription>
                        Routes that have been soft-deleted. Restore or permanently delete them.
                    </CardDescription>

                    <CardAction>
                        <Button as-child size="sm" variant="outline">
                            <Link :href="index().url">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Back to Routes
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <!-- Search -->
                    <div class="w-full max-w-sm">
                        <SearchInput
                            :route="trash().url"
                            :initial-value="props.filters.search"
                            placeholder="Search archived routes..."
                            :only="['routes', 'filters']"
                            :debounce="350"
                        />
                    </div>

                    <!-- Table -->
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Route Name</TableHead>
                                <TableHead>Gate</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Archived</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="routeItem in (props.routes.data as RouteRow[])"
                                :key="routeItem.id"
                            >
                                <TableCell class="font-medium capitalize">
                                    {{ routeItem.route_name }}
                                </TableCell>

                                <TableCell class="text-muted-foreground">
                                    {{ routeItem.gate?.gate_name ?? '—' }}
                                </TableCell>

                                <!-- Status badge (read-only on trash page) -->
                                <TableCell>
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            routeItem.status === 'active'
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-zinc-100 text-zinc-600',
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'h-1.5 w-1.5 rounded-full',
                                                routeItem.status === 'active' ? 'bg-green-500' : 'bg-zinc-400',
                                            ]"
                                        />
                                        {{ routeItem.status === 'active' ? 'Active' : 'Inactive' }}
                                    </span>
                                </TableCell>

                                <TableCell class="text-muted-foreground text-sm">
                                    {{ routeItem.deleted_at_human ?? '—' }}
                                </TableCell>

                                <!-- 3-dot actions -->
                                <TableCell class="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon" class="h-8 w-8">
                                                <MoreHorizontal class="h-4 w-4" />
                                                <span class="sr-only">Open menu</span>
                                            </Button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end" class="w-44">
                                            <!-- Restore -->
                                            <DropdownMenuItem
                                                class="flex items-center gap-2 cursor-pointer text-green-600 focus:text-green-700"
                                                @click="handleRestore(routeItem.id)"
                                            >
                                                <RotateCcw class="h-4 w-4" />
                                                Restore
                                            </DropdownMenuItem>

                                            <DropdownMenuSeparator />

                                            <!-- Force delete -->
                                            <DropdownMenuItem
                                                class="flex items-center gap-2 cursor-pointer text-destructive focus:text-destructive"
                                                @click="openDeleteDialog(routeItem.id)"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                                Delete Permanently
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>

                            <!-- Empty state -->
                            <TableRow v-if="props.routes.data.length === 0">
                                <TableCell colspan="5" class="py-10 text-center text-muted-foreground">
                                    No archived routes found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

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

        <!-- Force delete confirmation dialog -->
        <Dialog :open="deleteOpen" @update:open="deleteOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-destructive">
                        <Trash2 :size="18" />
                        Delete Permanently
                    </DialogTitle>
                    <DialogDescription>
                        This action <strong>cannot be undone</strong>. The route and all its stops
                        will be permanently removed from the database.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="outline" @click="deletingId = null">Cancel</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="confirmForceDelete">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete Permanently
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
