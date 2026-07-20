<script setup lang="ts">

import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';


import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';


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
import Input from '@/components/ui/input/Input.vue';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';


import {
    RiArrowDownSLine,
    RiArrowUpDownLine,
    RiArrowUpSLine,
    RiBuilding2Line,
    RiClipboardLine,
    RiCloseLine,
    RiExternalLinkLine,
    RiFileInfoLine,
    RiFilter2Line,
    RiMore2Line,
} from 'vue-remix-icons';


import InternalDispatchController from '@/actions/App/Http/Controllers/InternalDispatchController';
import { index as changeRequestsIndex } from '@/actions/App/Http/Controllers/DispatchChangeRequestController';


type CompanyItem = {
    id: number;
    company_name: string;
    company_code: string | null;
    company_email: string | null;
    company_phone: string | null;
    status: string | null;
    dispatches_count: number;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type SortField = 'company_name' | 'company_code' | 'status' | 'dispatches_count' | null;

type PaginatedCompanies = {
    data: CompanyItem[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};


const props = defineProps<{
    filters: {
        search: string;
        status?: string | null;
        minimum_dispatches?: number | null;
        sort_by?: SortField;
        sort_dir?: 'asc' | 'desc';
    };
    companies: PaginatedCompanies;
}>();

const sortBy  = ref<SortField>(props.filters.sort_by ?? null);
const sortDir = ref<'asc' | 'desc'>(props.filters.sort_dir ?? 'asc');
const filterOpen = ref(false);
const filterStatus = ref(props.filters.status || 'all');
const filterMinimumDispatches = ref(props.filters.minimum_dispatches ? String(props.filters.minimum_dispatches) : '');
const previewedCompany = ref<CompanyItem | null>(null);
const activeFilterCount = computed(() =>
    Number(filterStatus.value !== 'all') + Number(Boolean(filterMinimumDispatches.value)),
);

function filterParams() {
    return {
        search: props.filters.search || undefined,
        status: filterStatus.value === 'all' ? undefined : filterStatus.value,
        minimum_dispatches: filterMinimumDispatches.value || undefined,
        sort_by: sortBy.value ?? undefined,
        sort_dir: sortBy.value ? sortDir.value : undefined,
    };
}

function applyFilters() {
    router.get(InternalDispatchController.index().url, filterParams(), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        only: ['companies', 'filters'],
    });
    filterOpen.value = false;
}

function clearFilters() {
    filterStatus.value = 'all';
    filterMinimumDispatches.value = '';
    applyFilters();
}

function toggleSort(field: SortField) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDir.value = 'asc';
    }
    router.get(
        InternalDispatchController.index().url,
        filterParams(),
        { preserveScroll: true, preserveState: true, replace: true },
    );
}

function sortIcon(field: SortField) {
    if (sortBy.value !== field) return RiArrowUpDownLine;
    return sortDir.value === 'asc' ? RiArrowUpSLine : RiArrowDownSLine;
}

function sortIconClass(field: SortField) {
    return sortBy.value === field ? 'text-custom-primary' : 'text-custom-shadow/40';
}


const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dispatches', href: InternalDispatchController.index().url },
];


function prettyStatus(value: string | null | undefined) {
    return String(value ?? 'unknown')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function statusClass(status: string | null | undefined): string {
    switch (status) {
        case 'verified':
        case 'active':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'pending':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        default:
            return 'bg-slate-100 text-slate-500 border-0';
    }
}

function statusDot(status: string | null | undefined): string {
    switch (status) {
        case 'verified':
        case 'active':
            return 'bg-emerald-500';
        case 'pending':
            return 'bg-amber-400';
        default:
            return 'bg-slate-400';
    }
}
</script>

<template>
    <Head title="Dispatches" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4 lg:flex-row lg:items-stretch">
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row gap-2 items-center">
                    <div class="flex flex-col">
                        <CardTitle class="flex items-center gap-2">
                            <span class="font-semibold">Dispatches</span>
                        </CardTitle>
                        <CardDescription>Find a company and view its total dispatch records.</CardDescription>
                    </div>
                    <div class="flex flex-1 justify-end gap-2 items-center">
                        <DropdownMenu class="w-fit">
                            <DropdownMenuTrigger as-child class="m-0">
                                <div class="inline-flex">
                                    <Button variant="header-actions" class="text-custom-shadow" size="icon" aria-label="Open dispatch actions">
                                        <RiMore2Line class="h-4 w-4 shrink-0" />
                                    </Button>
                                </div>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-fit">
                                <DropdownMenuItem as-child class="group cursor-pointer">
                                    <Link :href="changeRequestsIndex().url" class="flex items-center">
                                        <RiFileInfoLine class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                        Change Requests
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </CardHeader>

                <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                    <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                        <div class="w-full">
                            <SearchInput
                                :route="InternalDispatchController.index().url"
                                placeholder="Search company..."
                                :initial-value="props.filters.search"
                                :only="['companies', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>
                        <Popover v-model:open="filterOpen">
                            <PopoverTrigger as-child>
                                <Button
                                    variant="header-actions"
                                    size="icon-text"
                                    class="rounded-full"
                                    :class="activeFilterCount ? 'bg-custom-secondary/20 transition-all duration-300 hover:bg-custom-secondary/80 hover:text-custom-bg-light' : ''"
                                >
                                    <RiFilter2Line class="h-3.5 w-3.5" />
                                    <span class="hidden lg:flex">
                                        {{ activeFilterCount ? `${activeFilterCount} filter${activeFilterCount === 1 ? '' : 's'} active` : 'Filter' }}
                                    </span>
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent align="end">
                                <div class="grid gap-y-2">
                                    <div class="flex flex-col gap-y-1">
                                        <p class="text-sm text-custom-shadow/80">Status</p>
                                        <Select v-model="filterStatus">
                                            <SelectTrigger class="w-full"><SelectValue placeholder="Any status" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">Any status</SelectItem>
                                                <SelectItem value="verified">Verified</SelectItem>
                                                <SelectItem value="for_verification">For verification</SelectItem>
                                                <SelectItem value="docs_completed">Documents completed</SelectItem>
                                                <SelectItem value="needs_revision">Needs revision</SelectItem>
                                                <SelectItem value="draft">Draft</SelectItem>
                                                <SelectItem value="rejected">Rejected</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div class="flex flex-col gap-y-1">
                                        <p class="text-sm text-custom-shadow/80">Minimum dispatches</p>
                                        <Input v-model="filterMinimumDispatches" type="number" min="0" placeholder="e.g. 5" class="bg-custom-bg" />
                                    </div>
                                    <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">
                                    <div class="flex items-center justify-between">
                                        <Button v-if="activeFilterCount" variant="destructive" size="sm" @click="clearFilters">Clear</Button>
                                        <div class="ml-auto flex items-center gap-2">
                                            <Button variant="ghost-outline" size="sm" @click="filterOpen = false">Cancel</Button>
                                            <Button variant="float-primary" size="sm" @click="applyFilters">Apply</Button>
                                        </div>
                                    </div>
                                </div>
                            </PopoverContent>
                        </Popover>
                    </div>

                    <Card
                        :class="[
                            'flex min-h-0 flex-1 max-h-fit flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none',
                            companies.data.length === 0 ? 'border-dashed' : 'border-solid',
                        ]"
                    >
                        <div v-if="companies.data.length > 0" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                            <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                                <div class="grid grid-cols-7 gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                    <button
                                        type="button"
                                        class="col-span-2 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 pl-3 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('company_name')"
                                    >
                                        Company
                                        <component :is="sortIcon('company_name')" class="h-3.5 w-3.5" :class="sortIconClass('company_name')" />
                                    </button>

                                    <button
                                        type="button"
                                        class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('company_code')"
                                    >
                                        Code
                                        <component :is="sortIcon('company_code')" class="h-3.5 w-3.5" :class="sortIconClass('company_code')" />
                                    </button>

                                    <div class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">
                                        Contact
                                    </div>

                                    <button
                                        type="button"
                                        class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('status')"
                                    >
                                        Status
                                        <component :is="sortIcon('status')" class="h-3.5 w-3.5" :class="sortIconClass('status')" />
                                    </button>

                                    <button
                                        type="button"
                                        class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('dispatches_count')"
                                    >
                                        Dispatches
                                        <component :is="sortIcon('dispatches_count')" class="h-3.5 w-3.5" :class="sortIconClass('dispatches_count')" />
                                    </button>

                                    <div class="col-span-1 flex h-10 items-center justify-end px-0 pr-3 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">
                                        Actions
                                    </div>
                                </div>
                            </div>

                            <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                <div
                                    v-for="(company, rowIndex) in companies.data"
                                    :key="company.id"
                                    :class="[
                                        'grid cursor-pointer grid-cols-7 items-center border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light',
                                        rowIndex === companies.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                        previewedCompany?.id === company.id ? 'bg-custom-secondary/10 text-custom-shadow' : '',
                                    ]"
                                    @click="previewedCompany = company"
                                >
                                    <div class="col-span-2 flex min-w-0 justify-start py-1.5 pl-3">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-custom-secondary/10 ring-1 ring-custom-bg-dark dark:ring-custom-bg-light">
                                                <RiBuilding2Line class="h-4 w-4 text-custom-primary" />
                                            </div>
                                            <div class="min-w-0">
                                                <div class="truncate text-sm font-semibold">
                                                    {{ company.company_name }}
                                                </div>
                                                <div class="truncate text-xs text-custom-shadow/70">
                                                    ID #{{ company.id }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-span-1 flex justify-start py-1.5">
                                        <span
                                            v-if="company.company_code"
                                            class="rounded bg-custom-bg px-2 py-0.5 font-mono text-xs font-semibold text-custom-shadow dark:bg-custom-bg-light"
                                        >
                                            {{ company.company_code }}
                                        </span>
                                        <span v-else class="text-sm text-custom-shadow/70">—</span>
                                    </div>

                                    <div class="col-span-1 flex min-w-0 justify-start py-1.5">
                                        <div class="min-w-0 space-y-1">
                                            <div class="flex min-w-0 items-center gap-1.5 text-xs text-custom-shadow/70">
                                                <span class="max-w-[180px] truncate">
                                                    {{ company.company_email || '—' }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-1.5 text-xs text-custom-shadow/70">
                                                <span class="truncate">{{ company.company_phone || '—' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-span-1 flex justify-start py-1.5">
                                        <Badge :class="['gap-1.5', statusClass(company.status)]">
                                            <span :class="['h-1.5 w-1.5 rounded-full', statusDot(company.status)]" />
                                            {{ prettyStatus(company.status) }}
                                        </Badge>
                                    </div>

                                    <div class="col-span-1 flex justify-start py-1.5">
                                        <div class="inline-flex items-center gap-1.5 rounded-full border border-custom-bg-dark bg-custom-bg px-3 py-1 text-xs font-semibold text-custom-shadow dark:border-custom-bg-light dark:bg-custom-bg-light">
                                            <RiClipboardLine class="h-3.5 w-3.5" />
                                            {{ company.dispatches_count }}
                                        </div>
                                    </div>

                                    <div class="col-span-1 flex justify-end py-1.5 pr-3 text-right" @click.stop>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="table-more"
                                                    size="icon-more"
                                                >
                                                    <RiMore2Line class="h-4 w-4" />
                                                    
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end" class="">
                                                <DropdownMenuLabel>
                                                    {{ company.company_name }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuItem
                                                    as-child
                                                    class="group"
                                                >
                                                    <Link
                                                        :href="InternalDispatchController.show(company.id).url"
                                                        class="flex items-center"
                                                    >
                                                        <RiExternalLinkLine class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                                        View
                                                    </Link>
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
                                    <p class="text-custom-shadow text-base font-semibold">No companies found</p>
                                    <p class="text-custom-shadow/80 text-sm">
                                        {{ activeFilterCount ? 'Try adjusting or clearing your filters.' : 'Try adjusting your search term.' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <InertiaPagination
                        v-if="companies.links?.length"
                        :links="companies.links"
                        :meta="{ from: companies.from, to: companies.to, total: companies.total }"
                    />
                </CardContent>
            </Card>

            <Card class="hidden min-h-0 lg:flex lg:h-full lg:w-100">
                <CardHeader
                    v-if="previewedCompany"
                    class="flex flex-row items-start justify-between gap-3"
                >
                    <div class="min-w-0">
                        <CardTitle class="truncate capitalize">
                            {{ previewedCompany.company_name }}
                        </CardTitle>
                        <CardDescription>Preview</CardDescription>
                    </div>
                    <Button
                        variant="header-actions"
                        size="icon"
                        class="h-8 w-8 shrink-0 rounded-full"
                        aria-label="Close company preview"
                        @click="previewedCompany = null"
                    >
                        <RiCloseLine class="h-4 w-4" />
                    </Button>
                </CardHeader>

                <CardContent
                    v-if="previewedCompany"
                    class="no-scrollbar min-h-0 flex-1 space-y-2 overflow-y-auto py-2"
                >
                    <div class="flex aspect-4/3 items-center justify-center overflow-hidden rounded-md border border-dashed border-custom-bg-dark bg-custom-bg text-custom-shadow/70 dark:border-none dark:bg-custom-bg-dark">
                        <RiBuilding2Line class="h-16 w-16" />
                    </div>

                    <div class="space-y-2 pt-2">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Status</span>
                            <Badge :class="['gap-1.5', statusClass(previewedCompany.status)]">
                                <span :class="['h-1.5 w-1.5 rounded-full', statusDot(previewedCompany.status)]" />
                                {{ prettyStatus(previewedCompany.status) }}
                            </Badge>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Company Code</span>
                            <span class="text-right font-mono text-sm">{{ previewedCompany.company_code || '—' }}</span>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-custom-shadow">Contact Information</p>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-3 rounded-md bg-custom-bg px-3 py-2 dark:bg-custom-bg-dark">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <span class="truncate text-sm">{{ previewedCompany.company_email || 'No email recorded' }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-3 rounded-md bg-custom-bg px-3 py-2 dark:bg-custom-bg-dark">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <span class="truncate text-sm">{{ previewedCompany.company_phone || 'No phone recorded' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-custom-shadow">Dispatch Records</p>
                                <span class="text-sm text-custom-shadow">{{ previewedCompany.dispatches_count }}</span>
                            </div>
                            <div class="flex items-center gap-2 rounded-md bg-custom-bg px-3 py-2 dark:bg-custom-bg-dark">
                                <RiClipboardLine class="h-4 w-4 text-custom-shadow/70" />
                                <span class="text-sm text-custom-shadow/70">
                                    {{ previewedCompany.dispatches_count === 1 ? '1 recorded dispatch' : `${previewedCompany.dispatches_count} recorded dispatches` }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">

                    <div class="flex items-center justify-end gap-2">
                        <Button as-child variant="float-primary" size="icon">
                            <Link :href="InternalDispatchController.show(previewedCompany.id).url">
                                <RiExternalLinkLine class="h-4 w-4" />
                            </Link>
                        </Button>
                    </div>
                </CardContent>

                <CardContent v-else class="flex min-h-0 flex-1 items-center justify-center">
                    <div class="max-w-60 space-y-1 text-center">
                        <p class="text-base font-semibold text-custom-shadow">No company selected</p>
                        <p class="text-sm text-custom-shadow/80">Click on a company to preview.</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
