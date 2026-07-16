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
import Input from '@/components/ui/input/Input.vue';
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


import {
    index,
    restore,
    trash,
} from '@/actions/App/Http/Controllers/RouteController';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';


import { RiArrowLeftSLine, RiFilter2Line, RiMore2Line, RiRestartLine } from 'vue-remix-icons';


import { computed, ref } from 'vue';


import { toast } from 'vue-sonner';


import { can } from '@/lib/can';

const canRestore = can('routes.restore');


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


const props = withDefaults(
    defineProps<{
        routes: PaginatedRoutes;
        filters?: {
            search: string | null;
            status: string | null;
            gate: string | null;
        };
    }>(),
    { filters: () => ({ search: null, status: null, gate: null }) },
);

const filterStatus = ref(props.filters.status ?? 'all');
const filterGate = ref(props.filters.gate ?? '');
const filterOpen = ref(false);

const activeFilterCount = computed(() => {
    let count = 0;
    if (filterStatus.value !== 'all') count++;
    if (filterGate.value) count++;
    return count;
});

function applyFilters() {
    router.get(
        trash().url,
        {
            search: props.filters.search || undefined,
            status: filterStatus.value === 'all' ? undefined : filterStatus.value,
            gate: filterGate.value || undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['routes', 'filters'],
        },
    );
    filterOpen.value = false;
}

function clearFilters() {
    filterStatus.value = 'all';
    filterGate.value = '';
    router.get(
        trash().url,
        { search: props.filters.search || undefined },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['routes', 'filters'],
        },
    );
    filterOpen.value = false;
}


const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Routes', href: index().url },
    { title: 'Archived Routes', href: trash().url },
];


const restoreOpen = ref(false);
const selectedRoute = ref<RouteRow | null>(null);


function openRestoreDialog(route: RouteRow) {
    selectedRoute.value = route;
    restoreOpen.value = true;
}

function closeRestoreDialog() {
    restoreOpen.value = false;
    selectedRoute.value = null;
}


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

</script>

<template>
    <Head title="Archived Routes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4">
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row items-start gap-3">
                    <Button as-child variant="header-actions" size="icon">
                        <Link :href="index().url" aria-label="Back to routes">
                            <RiArrowLeftSLine class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div class="flex min-w-0 flex-col">
                        <CardTitle class="font-semibold">Archived Routes</CardTitle>
                        <CardDescription>Restore archived routes to the active routes list.</CardDescription>
                    </div>
                </CardHeader>

                <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                    <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                        <div class="w-full">
                            <SearchInput
                                :route="`${trash().url}?status=${filterStatus === 'all' ? '' : filterStatus}&gate=${encodeURIComponent(filterGate)}`"
                                :initial-value="filters.search"
                                placeholder="Search archived routes..."
                                :only="['routes', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>

                        <Popover v-model:open="filterOpen">
                            <PopoverTrigger as-child>
                                <Button
                                    variant="header-actions"
                                    size="icon-text"
                                    class="rounded-full"
                                    :class="activeFilterCount > 0 ? 'bg-custom-secondary/20 transition-all duration-300 hover:bg-custom-secondary/80 hover:text-custom-bg-light' : ''"
                                >
                                    <RiFilter2Line class="h-3.5 w-3.5" />
                                    <span class="hidden lg:flex">
                                        {{ activeFilterCount > 0
                                            ? (activeFilterCount === 1 ? '1 filter active' : `${activeFilterCount} filters active`)
                                            : 'Filter' }}
                                    </span>
                                </Button>
                            </PopoverTrigger>

                            <PopoverContent align="end">
                                <div class="grid gap-y-2">
                                    <div class="flex flex-col gap-y-1">
                                        <p class="text-sm text-custom-shadow/80">Status</p>
                                        <Select v-model="filterStatus">
                                            <SelectTrigger class="w-full">
                                                <SelectValue placeholder="Any status" class="flex justify-start" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">Any status</SelectItem>
                                                <SelectItem value="active">Active</SelectItem>
                                                <SelectItem value="inactive">Inactive</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="flex flex-col gap-y-1">
                                        <p class="text-sm text-custom-shadow/80">Gate</p>
                                        <Input v-model="filterGate" placeholder="e.g. Gate 1" class="bg-custom-bg" />
                                    </div>

                                    <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light" />

                                    <div class="flex w-full flex-row items-center justify-between">
                                        <Button v-if="activeFilterCount > 0" size="sm" variant="destructive" @click="clearFilters">
                                            Clear
                                        </Button>
                                        <div class="ml-auto flex items-center gap-2">
                                            <Button variant="ghost-outline" size="sm" @click="filterOpen = false">Cancel</Button>
                                            <Button size="sm" variant="float-primary" @click="applyFilters">Apply</Button>
                                        </div>
                                    </div>
                                </div>
                            </PopoverContent>
                        </Popover>
                    </div>

                    <Card
                        :class="[
                            'flex min-h-0 max-h-fit flex-1 flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none',
                            props.routes.data.length === 0 ? 'border-dashed' : 'border-solid',
                        ]"
                    >
                        <div v-if="props.routes.data.length > 0" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                            <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                                <div class="grid grid-cols-5 gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                    <div class="flex h-10 items-center pl-3 text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Name</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Gate</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Status</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Archived At</div>
                                    <div class="flex h-10 items-center justify-end pr-3 text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Actions</div>
                                </div>
                            </div>

                            <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                <div
                                    v-for="(routeItem, rowIndex) in props.routes.data"
                                    :key="routeItem.id"
                                    :class="[
                                        'grid grid-cols-5 items-center gap-2 border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light',
                                        rowIndex === props.routes.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                    ]"
                                >
                                    <div class="flex py-1.5 pl-3 font-semibold capitalize">{{ routeItem.route_name }}</div>
                                    <div class="flex py-1.5">{{ routeItem.gate?.gate_name ?? '—' }}</div>
                                    <div class="flex py-1.5">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                routeItem.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-zinc-100 text-zinc-600',
                                            ]"
                                        >
                                            <span :class="['h-1.5 w-1.5 rounded-full', routeItem.status === 'active' ? 'animate-pulse bg-green-500' : 'bg-zinc-400']" />
                                            {{ routeItem.status === 'active' ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <div class="flex py-1.5 text-sm">{{ routeItem.deleted_at_human ?? '—' }}</div>
                                    <div class="flex justify-end py-1.5 pr-3" @click.stop>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="table-more" size="icon-more">
                                                    <RiMore2Line class="h-4 w-4" />
                                                    
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" class="">
                                                <DropdownMenuLabel>{{ routeItem.route_name }}</DropdownMenuLabel>
                                                <DropdownMenuItem v-if="canRestore" class="group" @click="openRestoreDialog(routeItem)">
                                                    <RiRestartLine class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                    Restore
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                            <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                                <img :src="emptyRafikiUrl" alt="" class="w-1/3 object-contain opacity-90" aria-hidden="true" />
                                <div class="space-y-1">
                                    <p class="text-base font-semibold text-custom-shadow">No archived routes found</p>
                                    <p class="text-sm text-custom-shadow/80">
                                        {{ filters.search || activeFilterCount > 0 ? 'Try adjusting your search or filters.' : 'Nothing has been archived yet.' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <InertiaPagination
                        :links="props.routes.links"
                        :meta="{ from: props.routes.from, to: props.routes.to, total: props.routes.total }"
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
                    <RiRestartLine class="h-4 w-4" />
                    Restore Route
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
