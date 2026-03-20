<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';

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
import { Badge } from '@/components/ui/badge';
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
    create,
    destroy,
    edit,
    index,
    show,
    trash,
    toggleStatus,
} from '@/actions/App/Http/Controllers/RouteController';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import {
    Archive,
    ChevronRight,
    Download,
    Eye,
    MoreHorizontal,
    Pencil,
    Plus,
    Power,
    Route as RouteIcon,
    Upload,
} from 'lucide-vue-next';

/* ── Types ──────────────────────────────────────────────────────── */
interface Gate {
    id: number;
    gate_name: string;
}

interface RouteRow {
    id: number;
    route_name: string;
    status: 'active' | 'inactive';
    created_at_human: string | null;
    gate: Gate | null;
}

/* ── Props ───────────────────────────────────────────────────────── */
const props = withDefaults(
    defineProps<{
        routes: any;
        filters?: { search: string | null };
    }>(),
    { filters: () => ({ search: null }) },
);

/* ── Breadcrumbs ─────────────────────────────────────────────────── */
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Routes', href: index().url },
];

/* ── Archive dialog ──────────────────────────────────────────────── */
const archivingRoute = ref<RouteRow | null>(null);
const archiveOpen    = ref(false);

function openArchiveDialog(route: RouteRow) {
    archivingRoute.value = route;
    archiveOpen.value    = true;
}

function confirmArchive() {
    if (!archivingRoute.value) return;
    router.delete(destroy(archivingRoute.value.id).url, {
        preserveScroll: true,
        onFinish: () => {
            archivingRoute.value = null;
            archiveOpen.value    = false;
        },
    });
}

/* ── Toggle status ───────────────────────────────────────────────── */
function handleToggleStatus(id: number) {
    router.patch(toggleStatus(id).url, {}, { preserveScroll: true });
}

/* ── Status helpers ──────────────────────────────────────────────── */
function statusClass(status: RouteRow['status']): string {
    return status === 'active'
        ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
        : 'bg-slate-100 text-slate-500 border-0';
}

function statusDot(status: RouteRow['status']): string {
    return status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400';
}

function toggleStatusClass(status: RouteRow['status']): string {
    return status === 'active'
        ? 'text-rose-600 focus:bg-rose-50 focus:text-rose-600'
        : 'text-emerald-700 focus:bg-emerald-50 focus:text-emerald-700';
}
</script>

<template>
    <Head title="Routes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-5">
                <CardHeader>
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            Routes
                        </CardTitle>
                        <CardDescription class="mt-1">
                            Manage and view all available routes in the system.
                        </CardDescription>
                    </div>

                    <CardAction class="flex items-center gap-2">
                        <Button
                            as-child
                            size="sm"
                            variant="outline"
                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                        >
                            <Link :href="trash().url">
                                <Archive class="mr-2 h-4 w-4" />
                                View Archived
                            </Link>
                        </Button>

                        <Button
                            as-child
                            size="sm"
                            class="rounded-lg bg-blue-700 text-white hover:bg-blue-800 border-0 shadow-sm"
                        >
                            <Link :href="create().url">
                                <Plus class="mr-2 h-4 w-4" />
                                Create Route
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">

                    <!-- Search + Import/Export -->
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="index().url"
                                :initial-value="props.filters.search"
                                placeholder="Search routes…"
                                :only="['routes', 'filters']"
                                :debounce="350"
                            />
                        </div>
                        <div class="flex gap-2 sm:justify-end">
                            <Button
                                size="sm"
                                variant="outline"
                                class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                            >
                                <Upload class="mr-2 h-4 w-4" />
                                Import
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                            >
                                <Download class="mr-2 h-4 w-4" />
                                Export
                            </Button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 hover:bg-muted/40">
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Route Name</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Gate</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Status</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Created</TableHead>
                                    <TableHead class="text-right text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Actions</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <!-- Empty state -->
                                <TableRow v-if="props.routes.data.length === 0" class="hover:bg-transparent">
                                    <TableCell colspan="5" class="py-20 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                                                <RouteIcon class="h-6 w-6 text-muted-foreground/40" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-foreground">No routes found</p>
                                                <p class="mt-0.5 text-xs text-muted-foreground">Try adjusting your search or create a new route.</p>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="routeItem in (props.routes.data as RouteRow[])"
                                    :key="routeItem.id"
                                    class="transition-colors hover:bg-muted/30"
                                >
                                    <!-- Route name -->
                                    <TableCell class="text-sm font-semibold capitalize">
                                        {{ routeItem.route_name }}
                                    </TableCell>

                                    <!-- Gate -->
                                    <TableCell>
                                        <span
                                            v-if="routeItem.gate"
                                            class="rounded bg-muted px-2 py-0.5 font-mono text-xs font-semibold"
                                        >
                                            {{ routeItem.gate.gate_name }}
                                        </span>
                                        <span v-else class="text-sm text-muted-foreground">—</span>
                                    </TableCell>

                                    <!-- Status -->
                                    <TableCell>
                                        <Badge :class="['gap-1.5', statusClass(routeItem.status)]">
                                            <span :class="['h-1.5 w-1.5 rounded-full', statusDot(routeItem.status)]" />
                                            {{ routeItem.status === 'active' ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Created -->
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ routeItem.created_at_human ?? '—' }}
                                    </TableCell>

                                    <!-- Actions -->
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

                                            <DropdownMenuContent align="end" class="w-52 rounded-xl border-slate-200 shadow-lg">
                                                <DropdownMenuLabel class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                                    {{ routeItem.route_name }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    as-child
                                                    class="rounded-lg text-blue-700 focus:bg-blue-50 focus:text-blue-700"
                                                >
                                                    <Link :href="show(routeItem.id).url" class="flex items-center">
                                                        <Eye class="mr-2 h-4 w-4" />
                                                        View
                                                        <ChevronRight class="ml-auto h-3.5 w-3.5 text-blue-400" />
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    as-child
                                                    class="rounded-lg text-amber-600 focus:bg-amber-50 focus:text-amber-700"
                                                >
                                                    <Link :href="edit(routeItem.id).url">
                                                        <Pencil class="mr-2 h-4 w-4" />
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    :class="['rounded-lg', toggleStatusClass(routeItem.status)]"
                                                    @click="handleToggleStatus(routeItem.id)"
                                                >
                                                    <Power class="mr-2 h-4 w-4" />
                                                    {{ routeItem.status === 'active' ? 'Set Inactive' : 'Set Active' }}
                                                </DropdownMenuItem>

                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    class="rounded-lg text-rose-600 focus:bg-rose-50 focus:text-rose-600"
                                                    @click="openArchiveDialog(routeItem)"
                                                >
                                                    <Archive class="mr-2 h-4 w-4" />
                                                    Archive
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

        <!-- ── Archive confirm dialog ─────────────────────────────── -->
        <AlertDialog v-model:open="archiveOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>Archive Route</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to archive
                        <span class="font-semibold text-foreground">{{ archivingRoute?.route_name ?? 'this route' }}</span>?
                        You can restore it later from the Archived Routes page.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg" @click="archivingRoute = null">
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        class="rounded-lg bg-rose-600 text-white hover:bg-rose-700 border-0"
                        @click="confirmArchive"
                    >
                        <Archive class="mr-2 h-4 w-4" />
                        Archive
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

    </AppLayout>
</template>