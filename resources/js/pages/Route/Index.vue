<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';

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
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

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
    RiAddLine,
    RiArchive2Line,
    RiArrowDownSLine,
    RiArrowUpDownLine,
    RiArrowUpSLine,
    RiEditLine,
    RiEyeLine,
    RiFilter2Line,
    RiMore2Line,
    RiShutDownLine,
} from 'vue-remix-icons';

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
const filterOpen = ref(false);

const hasActiveFilters = computed(() =>
    (statusFilter.value && statusFilter.value !== 'all') ||
    sortBy.value !== null
);

const activeFilterCount = computed(() => {
    let count = 0;
    if (statusFilter.value && statusFilter.value !== 'all') count++;
    return count;
});

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

    filterOpen.value = false;
}

function onStatusChange(val: string) {
    statusFilter.value = val;
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
    if (sortBy.value !== field) return RiArrowUpDownLine;
    return sortDir.value === 'asc' ? RiArrowUpSLine : RiArrowDownSLine;
}

function sortIconClass(field: SortField) {
    return sortBy.value === field ? 'text-custom-primary' : 'text-custom-shadow/40';
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
        ? 'text-foreground'
        : 'text-foreground';
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
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4">
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row gap-2">
                    <div class="flex flex-col">
                        <CardTitle class="flex items-center gap-2">
                            <span class="font-semibold">Routes</span>
                        </CardTitle>
                        <CardDescription>Manage and view all available routes in the system.</CardDescription>
                    </div>
                    <div class="flex flex-1 justify-end gap-2">
                        <div class="lg:flex items-center gap-2 sm:justify-end">
                            <Button
                                v-if="canCreate"
                                as-child
                                variant="float-primary"
                                class="hidden lg:flex"
                            >
                                <Link :href="create().url">
                                    <RiAddLine class="h-4 w-4 shrink-0" />
                                    <span>Add Route</span>
                                </Link>
                            </Button>

                            <DropdownMenu class="w-fit">
                                <DropdownMenuTrigger as-child class="m-0">
                                    <div class="inline-flex">
                                        <Button
                                            variant="header-actions"
                                            class="text-custom-shadow"
                                            size="icon"
                                        >
                                            <RiMore2Line class="h-4 w-4 shrink-0" />
                                        </Button>
                                    </div>
                                </DropdownMenuTrigger>

                                <DropdownMenuContent align="end" class="w-fit">
                                    <DropdownMenuItem
                                        v-if="canCreate"
                                        as-child
                                        class="cursor-pointer lg:hidden"
                                    >
                                        <Link :href="create().url" class="flex items-center">
                                            <RiAddLine class="h-4 w-4" />
                                            Add Route
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-if="canViewTrash"
                                        as-child
                                        class="cursor-pointer"
                                    >
                                        <Link :href="trash().url" class="flex items-center">
                                            <RiArchive2Line class="h-4 w-4" />
                                            Archives
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                    <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                        <div class="w-full">
                            <SearchInput
                                :route="index().url"
                                :initial-value="props.filters.search"
                                placeholder="Search routes…"
                                :only="['routes', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>

                        <div class="flex w-fit flex-row gap-2 lg:items-center lg:justify-between">
                            <Popover v-model:open="filterOpen">
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="header-actions"
                                        size="icon-text"
                                        class="rounded-full"
                                        :class="
                                            activeFilterCount > 0
                                                ? 'bg-custom-secondary/20 hover:bg-custom-secondary/80 hover:text-custom-bg-light transition-all duration-300 dark:hover:text-custom-shadow'
                                                : ''
                                        "
                                    >
                                        <RiFilter2Line class="h-3.5 w-3.5" />
                                        <span class="hidden lg:flex">
                                            {{
                                                activeFilterCount > 0
                                                    ? (activeFilterCount === 1 ? '1 filter active' : `${activeFilterCount} filters active`)
                                                    : 'Filter'
                                            }}
                                        </span>
                                    </Button>
                                </PopoverTrigger>

                                <PopoverContent align="end">
                                    <div class="grid gap-y-2">
                                        <div class="space-y-2">
                                            <p class="text-sm text-custom-shadow/80">
                                                Status
                                            </p>
                                            <Select
                                                :model-value="statusFilter"
                                                @update:model-value="onStatusChange"
                                            >
                                                <SelectTrigger class="w-full">
                                                    <SelectValue placeholder="Any status" class="flex justify-start" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all" class="cursor-pointer">
                                                        Any status
                                                    </SelectItem>
                                                    <SelectItem value="active" class="cursor-pointer">
                                                        Active
                                                    </SelectItem>
                                                    <SelectItem value="inactive" class="cursor-pointer">
                                                        Inactive
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">

                                        <div class="flex w-full flex-row items-center justify-between">
                                            <Button
                                                v-if="hasActiveFilters"
                                                size="sm"
                                                variant="destructive"
                                                @click="clearFilters"
                                            >
                                                Clear
                                            </Button>

                                            <div class="ml-auto flex items-center gap-2">
                                                <Button
                                                    variant="ghost-outline"
                                                    size="sm"
                                                    @click="filterOpen = false"
                                                >
                                                    Cancel
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="float-primary"
                                                    @click="applyFilters"
                                                >
                                                    Apply
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        </div>
                    </div>

                    <Card
                        :class="[
                            'flex min-h-0 flex-1 max-h-fit flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none',
                            props.routes.data.length === 0 ? 'border-dashed' : 'border-solid',
                        ]"
                    >
                        <div v-if="props.routes.data.length > 0" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                            <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                                <div class="grid grid-cols-5 gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                    <button
                                        type="button"
                                        class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 pl-3 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('route_name')"
                                    >
                                        Name
                                        <component
                                            :is="sortIcon('route_name')"
                                            class="h-3.5 w-3.5"
                                            :class="sortIconClass('route_name')"
                                        />
                                    </button>

                                    <button
                                        type="button"
                                        class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('gate_name')"
                                    >
                                        Gate
                                        <component
                                            :is="sortIcon('gate_name')"
                                            class="h-3.5 w-3.5"
                                            :class="sortIconClass('gate_name')"
                                        />
                                    </button>

                                    <button
                                        type="button"
                                        class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('status')"
                                    >
                                        Status
                                        <component
                                            :is="sortIcon('status')"
                                            class="h-3.5 w-3.5"
                                            :class="sortIconClass('status')"
                                        />
                                    </button>

                                    <button
                                        type="button"
                                        class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('created_at')"
                                    >
                                        Created
                                        <component
                                            :is="sortIcon('created_at')"
                                            class="h-3.5 w-3.5"
                                            :class="sortIconClass('created_at')"
                                        />
                                    </button>

                                    <div class="col-span-1 flex h-10 items-center justify-end px-0 pr-3 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">
                                        Actions
                                    </div>
                                </div>
                            </div>

                            <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                <div
                                    v-for="(routeItem, index) in props.routes.data"
                                    :key="routeItem.id"
                                    :class="[
                                        'grid grid-cols-5 items-center border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light',
                                        index === props.routes.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                    ]"
                                >
                                    <div class="col-span-1 flex justify-start py-1.5 pl-3 font-semibold capitalize">
                                        {{ routeItem.route_name }}
                                    </div>

                                    <div class="col-span-1 flex justify-start py-1.5">
                                        <span
                                            v-if="routeItem.gate"
                                            class="rounded bg-custom-bg px-2 py-0.5 font-mono text-xs font-semibold text-custom-shadow dark:bg-custom-bg-light"
                                        >
                                            {{ routeItem.gate.gate_name }}
                                        </span>
                                        <span v-else class="text-sm text-custom-shadow/70">—</span>
                                    </div>

                                    <div class="col-span-1 flex justify-start py-1.5">
                                        <Badge :class="['gap-1.5', statusClass(routeItem.status)]">
                                            <span :class="['h-1.5 w-1.5 rounded-full', statusDot(routeItem.status)]" />
                                            {{ routeItem.status === 'active' ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </div>

                                    <div class="col-span-1 flex justify-start py-1.5 text-sm text-custom-shadow/80">
                                        {{ routeItem.created_at_human ?? '—' }}
                                    </div>

                                    <div class="col-span-1 flex justify-end py-1.5 pr-3 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="table-more"
                                                    size="icon-more"
                                                >
                                                    <RiMore2Line class="h-4 w-4" />
                                                    <span class="sr-only">Open menu</span>
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end" class="w-fit rounded-lg shadow-lg">
                                                <DropdownMenuLabel class="text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">
                                                    {{ routeItem.route_name }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    as-child
                                                    class="cursor-pointer rounded-lg"
                                                >
                                                    <Link :href="show(routeItem.id).url" class="flex items-center">
                                                        <RiEyeLine class="h-4 w-4" />
                                                        View
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="canUpdate"
                                                    as-child
                                                    class="cursor-pointer rounded-lg"
                                                >
                                                    <Link :href="edit(routeItem.id).url">
                                                        <RiEditLine class="h-4 w-4" />
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="canToggle"
                                                    :class="['cursor-pointer rounded-lg', toggleStatusClass(routeItem.status)]"
                                                    @click="openToggleDialog(routeItem)"
                                                >
                                                    <RiShutDownLine class="h-4 w-4" />
                                                    {{ routeItem.status === 'active' ? 'Set Inactive' : 'Set Active' }}
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                            <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                                <img
                                    :src="emptyRafikiUrl"
                                    alt=""
                                    class="w-1/3 object-contain opacity-90"
                                    aria-hidden="true"
                                />
                                <div class="space-y-1">
                                    <p class="text-custom-shadow text-base font-semibold">No routes found</p>
                                    <p class="text-custom-shadow/80 text-sm">
                                        {{ hasActiveFilters ? 'Try adjusting or clearing your filters.' : 'Try adjusting your search or add a new route.' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </Card>

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
            <AlertDialogContent class="rounded-lg p-4">
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{ togglingRoute?.status === 'active' ? 'Set Route Inactive' : 'Set Route Active' }}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to set
                        <span class="font-semibold text-foreground">{{ togglingRoute?.route_name ?? 'this route' }}</span>
                        to
                        <span class="font-semibold" :class="togglingRoute?.status === 'active' ? 'text-foreground' : 'text-foreground'">
                            {{ togglingRoute?.status === 'active' ? 'inactive' : 'active' }}
                        </span>?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg cursor-pointer hover:bg-slate-100" @click="togglingRoute = null">
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        :class="[
                            'rounded-lg border-0 text-white cursor-pointer',
                            togglingRoute?.status === 'active'
                                ? 'bg-rose-600 hover:bg-rose-700'
                                : 'bg-primary'
                        ]"
                        @click="confirmToggle"
                    >
                        <RiShutDownLine class="h-4 w-4" />
                        {{ togglingRoute?.status === 'active' ? 'Set Inactive' : 'Set Active' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
