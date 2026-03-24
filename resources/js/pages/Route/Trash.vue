<script setup lang="ts">
/* ======================================================
   Shared UI
====================================================== */
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';

/* shadcn-vue */
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
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
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
import AppLayout from '@/layouts/AppLayout.vue';
import {
    forceDelete,
    index,
    restore,
    trash,
} from '@/actions/App/Http/Controllers/RouteController';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

/* ======================================================
   Icons
====================================================== */
import {
    ArchiveRestore,
    ArrowLeft,
    ChevronRight,
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

const canConfirmForceDelete = computed(() => confirmText.value.trim() === 'delete');

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

    router.post(
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
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-10 mt-3">
                <CardHeader class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <CardTitle>Archived Routes</CardTitle>
                        <CardDescription>
                            Routes that have been archived. Restore or permanently delete them.
                        </CardDescription>
                    </div>

                    <CardAction>
                        <Button size="sm" variant="outline" as-child>
                            <Link :href="index().url">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Back to Routes
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="w-full max-w-sm">
                        <SearchInput
                            :route="trash().url"
                            :initial-value="props.filters.search"
                            placeholder="Search archived routes..."
                            :only="['routes', 'filters']"
                            :debounce="350"
                        />
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Route Name</TableHead>
                                <TableHead>Gate</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Archived At</TableHead>
                                <TableHead class="w-[100px] text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="routeItem in props.routes.data"
                                :key="routeItem.id"
                            >
                                <TableCell class="font-medium capitalize">
                                    <div class="flex items-center gap-2">
                                        <RouteIcon class="h-4 w-4 shrink-0 text-muted-foreground" />
                                        {{ routeItem.route_name }}
                                    </div>
                                </TableCell>

                                <TableCell class="text-muted-foreground">
                                    {{ routeItem.gate?.gate_name ?? '—' }}
                                </TableCell>

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
                                                routeItem.status === 'active'
                                                    ? 'animate-pulse bg-green-500'
                                                    : 'bg-zinc-400',
                                            ]"
                                        />
                                        {{ routeItem.status === 'active' ? 'Active' : 'Inactive' }}
                                    </span>
                                </TableCell>

                                <TableCell class="text-sm text-muted-foreground">
                                    {{ routeItem.deleted_at_human ?? '—' }}
                                </TableCell>

                                <TableCell class="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="h-8 w-8 rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground"
                                            >
                                                <MoreHorizontal class="h-4 w-4" />
                                                <span class="sr-only">Open menu</span>
                                            </Button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent
                                            align="end"
                                            class="w-56 rounded-xl border-slate-200 shadow-lg"
                                        >
                                            <DropdownMenuLabel class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                                {{ routeItem.route_name }}
                                            </DropdownMenuLabel>
                                            <DropdownMenuSeparator />

                                            <DropdownMenuItem
                                                v-if="canRestore"
                                                class="rounded-lg text-emerald-700 focus:bg-emerald-50 focus:text-emerald-700"
                                                @click="openRestoreDialog(routeItem)"
                                            >
                                                <ArchiveRestore class="mr-2 h-4 w-4" />
                                                Restore
                                                <ChevronRight class="ml-auto h-3.5 w-3.5 text-emerald-400" />
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="props.routes.data.length === 0">
                                <TableCell
                                    colspan="5"
                                    class="py-10 text-center text-muted-foreground"
                                >
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
    </AppLayout>

    <AlertDialog v-if="canRestore" v-model:open="restoreOpen">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle class="flex items-center gap-2">
                    <ArchiveRestore class="h-5 w-5 text-emerald-600" />
                    Restore Route
                </AlertDialogTitle>
                <AlertDialogDescription>
                    Are you sure you want to restore
                    <span class="font-medium text-foreground">
                        {{ selectedRoute?.route_name ?? 'this route' }}
                    </span>?
                    It will be moved back to the active routes list.
                </AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <AlertDialogCancel @click="closeRestoreDialog">Cancel</AlertDialogCancel>
                <AlertDialogAction
                    class="bg-emerald-600 hover:bg-emerald-700 focus-visible:ring-emerald-500"
                    @click="restoreRoute"
                >
                    Restore Route
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>

    <AlertDialog v-if="canForceDelete" v-model:open="deleteOpen">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle class="flex items-center gap-2">
                    <Trash2 class="h-5 w-5 text-destructive" />
                    Delete Permanently
                </AlertDialogTitle>
                <AlertDialogDescription>
                    This action <span class="font-semibold text-destructive">cannot be undone</span>.
                    Type <span class="font-mono font-semibold text-destructive">delete</span> below
                    to permanently remove
                    <span class="font-medium text-foreground">
                        {{ selectedRoute?.route_name ?? 'this route' }}
                    </span>.
                </AlertDialogDescription>
            </AlertDialogHeader>

            <div class="space-y-2 px-1">
                <Label for="confirm_delete">Confirmation</Label>
                <Input
                    id="confirm_delete"
                    v-model="confirmText"
                    placeholder="Type delete to confirm"
                />
            </div>

            <AlertDialogFooter>
                <AlertDialogCancel @click="closeDeleteDialog">Cancel</AlertDialogCancel>
                <AlertDialogAction
                    class="bg-destructive text-destructive-foreground hover:bg-destructive/90 disabled:pointer-events-none disabled:opacity-50"
                    :disabled="!canConfirmForceDelete"
                    @click="forceDeleteRoute"
                >
                    Delete Permanently
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>