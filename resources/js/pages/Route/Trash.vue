<script setup lang="ts">
/* ======================================================
   Shared UI
====================================================== */
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';

/* shadcn-vue */
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
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

/* ======================================================
   Layout, Routing & Inertia
====================================================== */
import {
    forceDelete,
    index,
    restore,
    trash,
} from '@/actions/App/Http/Controllers/RouteController';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

/* ======================================================
   Icons
====================================================== */
import {
    ArchiveRestore,
    ArrowLeft,
    MoreHorizontal,
    Route as RouteIcon,
    Trash2,
} from 'lucide-vue-next';

/* ======================================================
   Vue Core
====================================================== */
import { computed, ref } from 'vue';

/* ======================================================
   Toaster
====================================================== */
import { toast } from 'vue-sonner';

/* ======================================================
   Permissions
====================================================== */
import { can } from '@/lib/can';

const canRestore = can('routes.restore');
const canForceDelete = can('routes.forceDelete');

/* ======================================================
   Types
====================================================== */
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

interface PaginatedRoutes {
    data: RouteRow[];
    links: any[];
    from: number | null;
    to: number | null;
    total: number;
}

/* ======================================================
   Props
====================================================== */
const props = withDefaults(
    defineProps<{
        routes: PaginatedRoutes;
        filters?: { search: string | null };
    }>(),
    { filters: () => ({ search: null }) },
);

/* ======================================================
   Breadcrumbs
====================================================== */
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Routes', href: index().url },
    { title: 'Archived Routes', href: trash().url },
];

/* ======================================================
   Dialog state
====================================================== */
const restoreOpen = ref(false);
const deleteOpen = ref(false);
const selectedRoute = ref<RouteRow | null>(null);
const confirmText = ref('');

const canConfirmForceDelete = computed(
    () => confirmText.value.trim() === 'delete',
);

/* ======================================================
   Dialog helpers
====================================================== */
function openRestoreDialog(route: RouteRow) {
    selectedRoute.value = route;
    restoreOpen.value = true;
}

function closeRestoreDialog() {
    restoreOpen.value = false;
    selectedRoute.value = null;
}

function openDeleteDialog(route: RouteRow) {
    selectedRoute.value = route;
    confirmText.value = '';
    deleteOpen.value = true;
}

function closeDeleteDialog() {
    deleteOpen.value = false;
    selectedRoute.value = null;
    confirmText.value = '';
}

/* ======================================================
   Actions
====================================================== */
function restoreRoute() {
    if (!selectedRoute.value) return;

    router.patch(
        restore(selectedRoute.value.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => closeRestoreDialog(),
            onError: () => toast.error('Failed to restore route.'),
        },
    );
}

function forceDeleteRoute() {
    if (!selectedRoute.value || !canConfirmForceDelete.value) return;

    router.delete(forceDelete(selectedRoute.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Route permanently deleted.');
            closeDeleteDialog();
        },
        onError: () => toast.error('Failed to permanently delete route.'),
    });
}
</script>

<template>
    <Head title="Archived Routes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <!-- <span>Change Requests</span> -->
                         <!-- TODO: make the text straight, not wrapped -->
                        <Button
                            as-child
                            variant="outline"
                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100 mr-2"
                        >
                            <Link :href="index().url">
                                <ArrowLeft class="h-4 w-4" />
                            </Link>
                        </Button>
                        Archives
                        <span class="ml-2 flex flex-1 items-center">
                            <hr class="h-px w-full border border-rose-500" />
                            <div class="border-7 border-rose-500 rounded-xs">
                                <div class="border-3 border-white rounded-xs"></div>
                            </div>
                        </span>
                    </CardTitle>
                    <CardDescription class="mt-1">
                        Routes that have been archived. Restore or permanently delete them.
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="trash().url"
                                :initial-value="filters.search"
                                placeholder="Search archived routes…"
                                :only="['routes', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader class="border-y border-slate-200">
                                <TableRow class="gap-2">
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Route Name</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Gate</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Status</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Archived At</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-right text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Actions</TableHead
                                    >
                                </TableRow>
                            </TableHeader>

                            <TableBody class="border-y border-slate-200">
                                <!-- Empty state -->
                                <TableRow
                                    v-if="props.routes.data.length === 0"
                                    class="hover:bg-transparent"
                                >
                                    <TableCell
                                        colspan="5"
                                        class="py-20 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center gap-3"
                                        >
                                            <div
                                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted"
                                            >
                                                <RouteIcon
                                                    class="h-6 w-6 text-muted-foreground/40"
                                                />
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-foreground"
                                                >
                                                    No archived routes
                                                </p>
                                                <p
                                                    class="mt-0.5 text-xs text-muted-foreground"
                                                >
                                                    Nothing has been archived
                                                    yet.
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="routeItem in props.routes.data"
                                    :key="routeItem.id"
                                    class="group transition-colors hover:bg-muted/30"
                                >
                                    <TableCell class="px-0 font-medium capitalize">
                                        <div class="flex items-center gap-2">
                                            <RouteIcon
                                                class="h-4 w-4 shrink-0 text-muted-foreground"
                                            />
                                            {{ routeItem.route_name }}
                                        </div>
                                    </TableCell>

                                    <TableCell class="px-0 text-muted-foreground">
                                        {{ routeItem.gate?.gate_name ?? '—' }}
                                    </TableCell>

                                    <TableCell class="px-0">
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
                                                    routeItem.status === 'active'
                                                        ? 'animate-pulse bg-green-500'
                                                        : 'bg-zinc-400',
                                                ]"
                                            />
                                            {{
                                                routeItem.status === 'active'
                                                    ? 'Active'
                                                    : 'Inactive'
                                            }}
                                        </span>
                                    </TableCell>

                                    <TableCell
                                        class="px-0 text-sm text-muted-foreground"
                                    >
                                        {{ routeItem.deleted_at_human ?? '—' }}
                                    </TableCell>

                                    <TableCell class="text-right px-0">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="outline"
                                                    class="rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground cursor-pointer"
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
                                                class="w-fit rounded-lg border-slate-200 shadow-lg"
                                            >
                                                <DropdownMenuLabel
                                                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                                                >
                                                    {{ routeItem.route_name }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    v-if="canRestore"
                                                    class="rounded-lg cursor-pointer hover:bg-slate-100"
                                                    @click="
                                                        openRestoreDialog(routeItem)
                                                    "
                                                >
                                                    <ArchiveRestore
                                                        class="h-4 w-4"
                                                    />
                                                    Restore
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

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

    <AlertDialog v-if="canRestore" v-model:open="restoreOpen">
        <AlertDialogContent class="rounded-lg p-4">
            <AlertDialogHeader>
                <AlertDialogTitle class="flex items-center gap-2">
                    Restore Route
                </AlertDialogTitle>
                <AlertDialogDescription>
                    Are you sure you want to restore
                    <span class="font-medium text-foreground">
                        {{ selectedRoute?.route_name ?? 'this route' }} </span
                    >? It will be moved back to the active routes list.
                </AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <AlertDialogCancel class="rounded-lg cursor-pointer hover:bg-slate-100" @click="closeRestoreDialog"
                    >Cancel</AlertDialogCancel
                >
                <AlertDialogAction
                    class="rounded-lg border-0 text-white cursor-pointer bg-primary hover:bg-primary/90"
                    @click="restoreRoute"
                >
                    <ArchiveRestore class="h-4 w-4" />
                    Restore Route
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
