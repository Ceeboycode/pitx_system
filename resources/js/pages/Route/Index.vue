<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';

import {
    Table,
    TableColumn,
    TableHeader,
    TableContent,
    TableRow,
    TableCard,
    TableData,
    TableMoreButton,
} from '@/components/ui/_table';
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
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    ArchiveRouteDialog,
    ToggleRouteStatusDialog,
} from '@/components/internal/route';
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
    edit,
    index,
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
    RiCloseLine,
    RiEditLine,
    RiExternalLinkLine,
    RiFilter2Line,
    RiMore2Line,
    RiShutDownLine,
} from 'vue-remix-icons';

import { PanelLayout } from '@/components/ui/_panels';

import { computed, onMounted, onUnmounted, ref } from 'vue';

const canCreate    = can('routes.create');
const canUpdate    = can('routes.update');
const canDelete    = can('routes.archive');
const canViewTrash = can('routes.viewTrash');
const canToggle    = can('routes.toggleStatus');

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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Routes', href: index().url },
];

const statusFilter = ref<string>(props.filters.status ?? 'all');
const sortBy = ref<SortField>(props.filters.sort_by ?? null);
const sortDir = ref<SortDir>(props.filters.sort_dir ?? 'asc');
const filterOpen = ref(false);
const previewedRoute = ref<RouteRow | null>(null);

function openPreview(route: RouteRow) {
    previewedRoute.value = route;
}

function selectAdjacentRoute(direction: 1 | -1) {
    if (!previewedRoute.value) return;

    const list = props.routes.data;
    const currentIndex = list.findIndex((r) => r.id === previewedRoute.value?.id);
    const nextIndex = currentIndex + direction;
    if (currentIndex === -1 || nextIndex < 0 || nextIndex >= list.length) return;

    openPreview(list[nextIndex]);
}

function handleRowNavigationKeydown(event: KeyboardEvent) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        selectAdjacentRoute(1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        selectAdjacentRoute(-1);
    }
}

onMounted(() => window.addEventListener('keydown', handleRowNavigationKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleRowNavigationKeydown));

const openMenus = ref<Record<number, boolean>>({});

const hasActiveFilters = computed(() =>
    (statusFilter.value && statusFilter.value !== 'all') ||
    sortBy.value !== null
);

const activeFilterCount = computed(() => {
    let count = 0;
    if (statusFilter.value && statusFilter.value !== 'all') count++;
    return count;
});

function currentFilterParams(): Record<string, string | undefined> {
    return {
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        sort_by: sortBy.value ?? undefined,
        sort_dir: sortBy.value ? sortDir.value : undefined,
    };
}

function applyFilters(overrides: Record<string, string | undefined> = {}) {
    router.get(
        index().url,
        {
            search: props.filters.search ?? undefined,
            ...currentFilterParams(),
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

const archivingRoute = ref<RouteRow | null>(null);
const archiveOpen = ref(false);

function openArchiveDialog(route: RouteRow) {
    archivingRoute.value = route;
    archiveOpen.value = true;
}

const togglingRoute = ref<RouteRow | null>(null);
const toggleOpen = ref(false);

function openToggleDialog(route: RouteRow) {
    togglingRoute.value = route;
    toggleOpen.value = true;
}
</script>

<template>
    <Head title="Routes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
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
                                        class="cursor-pointer group"
                                    >
                                        <Link :href="trash().url" class="flex items-center">
                                            <RiArchive2Line class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow transition-all duration-200" />
                                            Archives
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 pt-2">
                    <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                        <div class="w-full">
                            <SearchInput
                                :route="index().url"
                                :initial-value="props.filters.search"
                                placeholder="Search routes..."
                                :only="['routes', 'filters', 'flash']"
                                :debounce="350"
                                :extra-params="currentFilterParams"
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
                                                ? 'bg-custom-secondary/20 hover:bg-custom-secondary/80 hover:text-custom-bg-light transition-all duration-200 dark:hover:text-custom-shadow'
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
                                        <div class="flex flex-col gap-y-1">
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
                                                    @click="applyFilters()"
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

                    <TableCard :table-data-length="props.routes.data.length">
                        <Table v-if="props.routes.data.length > 0">
                            <TableHeader>
                                <TableColumn class="p-0">
                                    <button
                                        type="button"
                                        class="flex h-10 w-full cursor-pointer select-none items-center justify-start gap-1.5 pl-3 pr-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('route_name')"
                                    >
                                        Name
                                        <component
                                            :is="sortIcon('route_name')"
                                            class="h-3.5 w-3.5"
                                            :class="sortIconClass('route_name')"
                                        />
                                    </button>
                                </TableColumn>

                                <TableColumn class="p-0">
                                    <button
                                        type="button"
                                        class="flex h-10 w-full cursor-pointer select-none items-center justify-start gap-1.5 pl-3 pr-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('gate_name')"
                                    >
                                        Gate
                                        <component
                                            :is="sortIcon('gate_name')"
                                            class="h-3.5 w-3.5"
                                            :class="sortIconClass('gate_name')"
                                        />
                                    </button>
                                </TableColumn>

                                <TableColumn class="p-0">
                                    <button
                                        type="button"
                                        class="flex h-10 w-full cursor-pointer select-none items-center justify-start gap-1.5 pl-3 pr-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('status')"
                                    >
                                        Status
                                        <component
                                            :is="sortIcon('status')"
                                            class="h-3.5 w-3.5"
                                            :class="sortIconClass('status')"
                                        />
                                    </button>
                                </TableColumn>

                                <TableColumn class="p-0">
                                    <button
                                        type="button"
                                        class="flex h-10 w-full cursor-pointer select-none items-center justify-start gap-1.5 pl-3 pr-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('created_at')"
                                    >
                                        Created
                                        <component
                                            :is="sortIcon('created_at')"
                                            class="h-3.5 w-3.5"
                                            :class="sortIconClass('created_at')"
                                        />
                                    </button>
                                </TableColumn>
                            </TableHeader>

                            <TableContent>
                                <TableRow
                                    v-for="(routeItem, rowIndex) in props.routes.data"
                                    :key="routeItem.id"
                                    :class="[
                                        rowIndex === props.routes.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                        previewedRoute?.id === routeItem.id ? 'bg-custom-secondary/10' : '',
                                    ]"
                                    :status="routeItem.status === 'inactive' ? 'inactive' : 'default'"
                                    @click.left="openPreview(routeItem)"
                                    @dblclick="router.visit(edit(routeItem.id).url)"
                                >
                                    <TableData class="pl-3 font-semibold capitalize">
                                        <span class="truncate">{{ routeItem.route_name }}</span>
                                    </TableData>

                                    <TableData>
                                        <span
                                            v-if="routeItem.gate"
                                            class="truncate rounded bg-custom-bg px-2 py-0.5 font-mono text-xs font-semibold text-custom-shadow dark:bg-custom-bg-light"
                                        >
                                            {{ routeItem.gate.gate_name }}
                                        </span>
                                        <span v-else class="text-sm text-custom-shadow/70">—</span>
                                    </TableData>

                                    <TableData>
                                        <Badge :class="['gap-1.5', statusClass(routeItem.status)]">
                                            <span :class="['h-1.5 w-1.5 rounded-full', statusDot(routeItem.status)]" />
                                            {{ routeItem.status === 'active' ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </TableData>

                                    <TableData class="text-sm text-custom-shadow/80">
                                        <span class="truncate">{{ routeItem.created_at_human ?? '—' }}</span>
                                    </TableData>

                                    <TableMoreButton
                                        :open="openMenus[routeItem.id] ?? false"
                                        @update:open="(value) => (openMenus[routeItem.id] = value)"
                                    >
                                        <DropdownMenuLabel>
                                            {{ routeItem.route_name }}
                                        </DropdownMenuLabel>
                                        <!-- <DropdownMenuItem
                                            as-child
                                            class="group"
                                        >
                                            <Link :href="show(routeItem.id).url" class="flex items-center">
                                                <RiExternalLinkLine class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow transition-all duration-200" />
                                                View
                                            </Link>
                                        </DropdownMenuItem> -->

                                        <!-- I JUST THINK NAAAA PAG NI VIEW NILA, SAME NA YUN AS HAVING THE TOOLS TO EDIT, KAYA WALA TO -->

                                        <DropdownMenuItem
                                            v-if="canUpdate"
                                            as-child
                                            class="group"
                                        >
                                            <Link :href="edit(routeItem.id).url">
                                                <RiExternalLinkLine class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow transition-all duration-200" />
                                                <!-- <RiEditLine class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow transition-all duration-200" /> -->
                                                View
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="canToggle"
                                            :class="['group', toggleStatusClass(routeItem.status)]"
                                            @click="openToggleDialog(routeItem)"
                                        >
                                            <RiShutDownLine class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow transition-all duration-200" />
                                            <span class="text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow transition-all duration-200">{{ routeItem.status === 'active' ? 'Inactivate' : 'Activate' }}</span>
                                        </DropdownMenuItem>
                                    </TableMoreButton>
                                </TableRow>
                            </TableContent>
                        </Table>

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
                    </TableCard>

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

            <Card class="hidden min-h-0 lg:flex lg:h-full lg:w-100">
                <CardHeader v-if="previewedRoute" class="flex flex-row items-start justify-between gap-3">
                    <div class="min-w-0">
                        <CardTitle class="truncate capitalize">{{ previewedRoute.route_name }}</CardTitle>
                        <CardDescription>Preview</CardDescription>
                    </div>
                    <Button variant="header-actions" size="icon" class="h-8 w-8 shrink-0 rounded-full" @click="previewedRoute = null">
                        <RiCloseLine class="h-4 w-4" />
                    </Button>
                </CardHeader>

                <CardContent v-if="previewedRoute" class="no-scrollbar min-h-0 flex-1 space-y-2 overflow-y-auto pt-2">
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Status</span>
                            <Badge :class="['gap-1.5', statusClass(previewedRoute.status)]">
                                <span :class="['h-1.5 w-1.5 rounded-full', statusDot(previewedRoute.status)]" />
                                {{ previewedRoute.status === 'active' ? 'Active' : 'Inactive' }}
                            </Badge>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Gate</span>
                            <span class="text-right text-sm">{{ previewedRoute.gate?.gate_name || 'Not assigned' }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Created</span>
                            <span class="text-right text-sm">{{ previewedRoute.created_at_human || 'Not recorded' }}</span>
                        </div>
                    </div>

                    <hr class="my-4 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">

                    <div class="flex flex-wrap items-center gap-2">
                        <Button v-if="canUpdate" as-child variant="ghost-outline" size="icon-text">
                            <Link :href="edit(previewedRoute.id).url">
                                <RiEditLine class="h-4 w-4" />
                                Edit
                            </Link>
                        </Button>
                        <Button v-if="canDelete" variant="destructive" size="icon-text" @click="openArchiveDialog(previewedRoute)">
                            <RiArchive2Line class="h-4 w-4" />
                            Archive
                        </Button>
                        <Button v-if="canToggle" :variant="previewedRoute.status === 'active' ? 'destructive' : 'ghost-outline'" size="icon-text" @click="openToggleDialog(previewedRoute)">
                            <RiShutDownLine class="h-4 w-4" />
                            {{ previewedRoute.status === 'active' ? 'Inactivate' : 'Activate' }}
                        </Button>
                        <Button as-child variant="float-primary" size="icon">
                            <Link :href="edit(previewedRoute.id).url">
                                <RiExternalLinkLine class="h-4 w-4" />
                            </Link>
                        </Button>
                    </div>
                </CardContent>

                <CardContent v-else class="flex min-h-0 flex-1 items-center justify-center">
                    <div class="max-w-60 space-y-1 text-center">
                        <p class="text-base font-semibold text-custom-shadow">No route selected</p>
                        <p class="text-sm text-custom-shadow/80">Click on a route to preview.</p>
                    </div>
                </CardContent>
            </Card>
        </PanelLayout>

        <ToggleRouteStatusDialog v-model:open="toggleOpen" :route="togglingRoute" />
        <ArchiveRouteDialog v-model:open="archiveOpen" :route="archivingRoute" />
    </AppLayout>
</template>
