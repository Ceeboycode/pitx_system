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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    toggleStatus,
    trash,
} from '@/actions/App/Http/Controllers/RouteController';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { can } from '@/lib/can';

import {
    Archive,
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    ChevronRight,
    Download,
    Eye,
    Filter,
    MoreHorizontal,
    Pencil,
    Plus,
    Power,
    Route as RouteIcon,
    Upload,
    X,
} from 'lucide-vue-next';

import { computed, ref } from 'vue';

/* ── Permissions ─────────────────────────────────────────────────── */
const canCreate    = can('routes.create');
const canUpdate    = can('routes.update');
const canDelete    = can('routes.archive');
const canViewTrash = can('routes.viewTrash');
const canToggle    = can('routes.toggleStatus');

/* ── Types ──────────────────────────────────────────────────────── */
interface Gate {
    id: number;
    gate_name: string;
}

type RouteStatus = 'active' | 'inactive' | null;
type SortField = 'route_name' | 'gate_name' | 'status' | 'created_at' | null;
type SortDir = 'asc' | 'desc';

interface RouteRow {
    id: number;
    route_name: string;
    status: RouteStatus;
    created_at_human: string | null;
    gate: Gate | null;
}

/* ── Props ───────────────────────────────────────────────────────── */
const props = withDefaults(
    defineProps<{
        routes: {
            data: RouteRow[];
            links: Array<{ url: string | null; label: string; active: boolean }>;
            from: number | null;
            to: number | null;
            total: number;
        };
        filters?: {
            search: string | null;
            status: string | null;
            sort_by: SortField;
            sort_dir: SortDir;
        };
    }>(),
    {
        filters: () => ({
            search: null,
            status: null,
            sort_by: null,
            sort_dir: 'asc',
        }),
    },
);

/* ── Breadcrumbs ─────────────────────────────────────────────────── */
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Routes', href: index().url },
];

/* ── Filter & Sort state ─────────────────────────────────────────── */
const statusFilter = ref<string>(props.filters.status ?? 'all');
const sortBy = ref<SortField>(props.filters.sort_by ?? null);
const sortDir = ref<SortDir>(props.filters.sort_dir ?? 'asc');

const hasActiveFilters = computed(() =>
    (statusFilter.value && statusFilter.value !== 'all') ||
    sortBy.value !== null
);

function applyFilters(overrides: Record<string, string | undefined> = {}) {
    router.get(
        index().url,
        {
            search: props.filters.search ?? undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
            sort_by: sortBy.value ?? undefined,
            sort_dir: sortBy.value ? sortDir.value : undefined,
            ...overrides,
        },
        {
            preserveState: true,
            replace: true,
            only: ['routes', 'filters', 'flash'],
        },
    );
}

function onStatusChange(val: string) {
    statusFilter.value = val;
    applyFilters();
}

function toggleSort(field: SortField) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDir.value = 'asc';
    }

    applyFilters();
}

function clearFilters() {
    statusFilter.value = 'all';
    sortBy.value = null;
    sortDir.value = 'asc';

    applyFilters({
        status: undefined,
        sort_by: undefined,
        sort_dir: undefined,
    });
}

function sortIcon(field: SortField) {
    if (sortBy.value !== field) return ArrowUpDown;
    return sortDir.value === 'asc' ? ArrowUp : ArrowDown;
}

function sortIconClass(field: SortField) {
    return sortBy.value === field ? 'text-blue-600' : 'text-muted-foreground/40';
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

/* ── Archive dialog ──────────────────────────────────────────────── */
const archivingRoute = ref<RouteRow | null>(null);
const archiveOpen = ref(false);

function openArchiveDialog(route: RouteRow) {
    archivingRoute.value = route;
    archiveOpen.value = true;
}

function confirmArchive() {
    if (!archivingRoute.value) return;

    router.delete(destroy(archivingRoute.value.id).url, {
        preserveScroll: true,
        onFinish: () => {
            archivingRoute.value = null;
            archiveOpen.value = false;
        },
    });
}

/* ── Toggle status ───────────────────────────────────────────────── */
function handleToggleStatus(id: number) {
    router.patch(toggleStatus(id).url, {}, { preserveScroll: true });
}

const togglingRoute = ref<RouteRow | null>(null);
const toggleOpen = ref(false);

function openToggleDialog(route: RouteRow) {
    togglingRoute.value = route;
    toggleOpen.value = true;
}

function confirmToggle() {
    if (!togglingRoute.value) return;
    router.patch(toggleStatus(togglingRoute.value.id).url, {}, {
        preserveScroll: true,
        onFinish: () => {
            togglingRoute.value = null;
            toggleOpen.value = false;
        },
    });
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
                            <RouteIcon class="h-5 w-5 text-blue-700" />
                            Routes
                        </CardTitle>
                        <CardDescription class="mt-1">
                            Manage and view all available routes in the system.
                        </CardDescription>
                    </div>

                    <CardAction class="flex items-center gap-2">
                        <Button
                            v-if="canViewTrash"
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
                            v-if="canCreate"
                            as-child
                            size="sm"
                            class="rounded-lg border-0 bg-blue-700 text-white shadow-sm hover:bg-blue-800"
                        >
                            <Link :href="create().url">
                                <Plus class="mr-2 h-4 w-4" />
                                Create Route
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="index().url"
                                :initial-value="props.filters.search"
                                placeholder="Search routes…"
                                :only="['routes', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>
                    </div>

                    <!-- Row 2: Filters + Sort -->
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                            <Filter class="h-3.5 w-3.5" />
                            Filter
                        </div>

                        <Select :model-value="statusFilter" @update:model-value="onStatusChange">
                            <SelectTrigger class="h-8 w-40 rounded-lg border-slate-200 text-xs">
                                <SelectValue placeholder="All Statuses" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="all" class="text-xs">All Statuses</SelectItem>
                                <SelectItem value="active" class="text-xs">Active</SelectItem>
                                <SelectItem value="inactive" class="text-xs">Inactive</SelectItem>
                            </SelectContent>
                        </Select>

                        <div class="ml-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                            <ArrowUpDown class="h-3.5 w-3.5" />
                            Sort
                        </div>

                        <Select
                            :model-value="sortBy ?? 'none'"
                            @update:model-value="(val) => { sortBy = val === 'none' ? null : val as SortField; applyFilters(); }"
                        >
                            <SelectTrigger class="h-8 w-40 rounded-lg border-slate-200 text-xs">
                                <SelectValue placeholder="Sort by…" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="none" class="text-xs">No Sort</SelectItem>
                                <SelectItem value="route_name" class="text-xs">Route Name</SelectItem>
                                <SelectItem value="gate_name" class="text-xs">Gate</SelectItem>
                                <SelectItem value="status" class="text-xs">Status</SelectItem>
                                <SelectItem value="created_at" class="text-xs">Created Date</SelectItem>
                            </SelectContent>
                        </Select>

                        <Button
                            v-if="sortBy"
                            size="sm"
                            variant="outline"
                            class="h-8 rounded-lg border-slate-200 px-3 text-xs text-slate-600 hover:bg-slate-100"
                            @click="sortDir = sortDir === 'asc' ? 'desc' : 'asc'; applyFilters()"
                        >
                            <ArrowUp v-if="sortDir === 'asc'" class="mr-1.5 h-3.5 w-3.5 text-blue-600" />
                            <ArrowDown v-else class="mr-1.5 h-3.5 w-3.5 text-blue-600" />
                            {{ sortDir === 'asc' ? 'Ascending' : 'Descending' }}
                        </Button>

                        <div v-if="hasActiveFilters" class="ml-auto flex items-center gap-2">
                            <Badge class="gap-1 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700 hover:bg-blue-50">
                                <Filter class="h-3 w-3" />
                                Filters active
                            </Badge>

                            <Button
                                size="sm"
                                variant="ghost"
                                class="h-7 rounded-lg px-2 text-xs text-muted-foreground hover:text-rose-600"
                                @click="clearFilters"
                            >
                                <X class="mr-1 h-3.5 w-3.5" />
                                Clear
                            </Button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 hover:bg-muted/40">
                                    <TableHead
                                        class="cursor-pointer select-none text-[11px] font-bold uppercase tracking-widest text-muted-foreground hover:text-foreground"
                                        @click="toggleSort('route_name')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Route Name
                                            <component
                                                :is="sortIcon('route_name')"
                                                class="h-3.5 w-3.5"
                                                :class="sortIconClass('route_name')"
                                            />
                                        </div>
                                    </TableHead>

                                    <TableHead
                                        class="cursor-pointer select-none text-[11px] font-bold uppercase tracking-widest text-muted-foreground hover:text-foreground"
                                        @click="toggleSort('gate_name')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Gate
                                            <component
                                                :is="sortIcon('gate_name')"
                                                class="h-3.5 w-3.5"
                                                :class="sortIconClass('gate_name')"
                                            />
                                        </div>
                                    </TableHead>

                                    <TableHead
                                        class="cursor-pointer select-none text-[11px] font-bold uppercase tracking-widest text-muted-foreground hover:text-foreground"
                                        @click="toggleSort('status')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Status
                                            <component
                                                :is="sortIcon('status')"
                                                class="h-3.5 w-3.5"
                                                :class="sortIconClass('status')"
                                            />
                                        </div>
                                    </TableHead>

                                    <TableHead
                                        class="cursor-pointer select-none text-[11px] font-bold uppercase tracking-widest text-muted-foreground hover:text-foreground"
                                        @click="toggleSort('created_at')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Created
                                            <component
                                                :is="sortIcon('created_at')"
                                                class="h-3.5 w-3.5"
                                                :class="sortIconClass('created_at')"
                                            />
                                        </div>
                                    </TableHead>

                                    <TableHead class="text-right text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow v-if="props.routes.data.length === 0" class="hover:bg-transparent">
                                    <TableCell colspan="5" class="py-20 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                                                <RouteIcon class="h-6 w-6 text-muted-foreground/40" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-foreground">No routes found</p>
                                                <p class="mt-0.5 text-xs text-muted-foreground">
                                                    {{ hasActiveFilters ? 'Try adjusting your filters or search.' : 'Try adjusting your search.' }}
                                                </p>
                                            </div>
                                            <Button
                                                v-if="hasActiveFilters"
                                                size="sm"
                                                variant="outline"
                                                class="mt-1 h-8 rounded-lg text-xs"
                                                @click="clearFilters"
                                            >
                                                <X class="mr-1.5 h-3.5 w-3.5" />
                                                Clear filters
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="routeItem in props.routes.data"
                                    :key="routeItem.id"
                                    class="transition-colors hover:bg-muted/30"
                                >
                                    <TableCell class="text-sm font-semibold capitalize">
                                        {{ routeItem.route_name }}
                                    </TableCell>

                                    <TableCell>
                                        <span
                                            v-if="routeItem.gate"
                                            class="rounded bg-muted px-2 py-0.5 font-mono text-xs font-semibold"
                                        >
                                            {{ routeItem.gate.gate_name }}
                                        </span>
                                        <span v-else class="text-sm text-muted-foreground">—</span>
                                    </TableCell>

                                    <TableCell>
                                        <Badge :class="['gap-1.5', statusClass(routeItem.status)]">
                                            <span :class="['h-1.5 w-1.5 rounded-full', statusDot(routeItem.status)]" />
                                            {{ routeItem.status === 'active' ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </TableCell>

                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ routeItem.created_at_human ?? '—' }}
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
                                                    v-if="canUpdate"
                                                    as-child
                                                    class="rounded-lg text-amber-600 focus:bg-amber-50 focus:text-amber-700"
                                                >
                                                    <Link :href="edit(routeItem.id).url">
                                                        <Pencil class="mr-2 h-4 w-4" />
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="canToggle"
                                                    :class="['rounded-lg', toggleStatusClass(routeItem.status)]"
                                                    @click="openToggleDialog(routeItem)"
                                                >
                                                    <Power class="mr-2 h-4 w-4" />
                                                    {{ routeItem.status === 'active' ? 'Set Inactive' : 'Set Active' }}
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

        <!-- Toggle status confirmation -->
        <AlertDialog v-model:open="toggleOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{ togglingRoute?.status === 'active' ? 'Set Route Inactive' : 'Set Route Active' }}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to set
                        <span class="font-semibold text-foreground">{{ togglingRoute?.route_name ?? 'this route' }}</span>
                        to
                        <span class="font-semibold" :class="togglingRoute?.status === 'active' ? 'text-rose-600' : 'text-emerald-600'">
                            {{ togglingRoute?.status === 'active' ? 'inactive' : 'active' }}
                        </span>?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg" @click="togglingRoute = null">
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        :class="[
                            'rounded-lg border-0 text-white',
                            togglingRoute?.status === 'active'
                                ? 'bg-rose-600 hover:bg-rose-700'
                                : 'bg-emerald-600 hover:bg-emerald-700'
                        ]"
                        @click="confirmToggle"
                    >
                        <Power class="mr-2 h-4 w-4" />
                        {{ togglingRoute?.status === 'active' ? 'Set Inactive' : 'Set Active' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>