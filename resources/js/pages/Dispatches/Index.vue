<script setup lang="ts">
/* ======================================================
   Layout, Routing & Inertia
====================================================== */
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

/* ======================================================
   Shared UI
====================================================== */
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';

/* shadcn-vue */
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

/* ======================================================
   Icons
====================================================== */
import {
    RiArrowDownSLine,
    RiArrowUpDownLine,
    RiArrowUpSLine,
    RiBuilding2Line,
    RiClipboardLine,
    RiEyeLine,
    RiMailLine,
    RiMore2Line,
    RiPhoneLine,
} from 'vue-remix-icons';

/* ======================================================
   Routing (Wayfinder)
====================================================== */
import InternalDispatchController from '@/actions/App/Http/Controllers/InternalDispatchController';

/* ======================================================
   Types
====================================================== */
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

/* ======================================================
   Props
====================================================== */
const props = defineProps<{
    filters: {
        search: string;
        sort_by?: SortField;
        sort_dir?: 'asc' | 'desc';
    };
    companies: PaginatedCompanies;
}>();

const sortBy  = ref<SortField>(props.filters.sort_by ?? null);
const sortDir = ref<'asc' | 'desc'>(props.filters.sort_dir ?? 'asc');

function toggleSort(field: SortField) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDir.value = 'asc';
    }
    router.get(
        InternalDispatchController.index().url,
        {
            search: props.filters.search || undefined,
            sort_by: sortBy.value ?? undefined,
            sort_dir: sortBy.value ? sortDir.value : undefined,
        },
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

/* ======================================================
   Breadcrumbs
====================================================== */
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dispatches', href: InternalDispatchController.index().url },
];

/* ======================================================
   Helpers
====================================================== */
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
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4">
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row gap-2">
                    <div class="flex flex-col">
                        <CardTitle class="flex items-center gap-2">
                            <span class="font-semibold">Dispatches</span>
                        </CardTitle>
                        <CardDescription>Find a company and view its total dispatch records.</CardDescription>
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
                                        'grid grid-cols-7 items-center border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light',
                                        rowIndex === companies.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                    ]"
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
                                                <RiMailLine class="h-3.5 w-3.5 shrink-0" />
                                                <span class="max-w-[180px] truncate">
                                                    {{ company.company_email || '—' }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-1.5 text-xs text-custom-shadow/70">
                                                <RiPhoneLine class="h-3.5 w-3.5 shrink-0" />
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

                                    <div class="col-span-1 flex justify-end py-1.5 pr-3 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="table-more"
                                                    size="icon-more"
                                                >
                                                    <RiMore2Line class="h-4 w-4" />
                                                    <span class="sr-only">Open actions</span>
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end" class="w-fit rounded-lg shadow-lg">
                                                <DropdownMenuLabel class="text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">
                                                    {{ company.company_name }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    as-child
                                                    class="cursor-pointer rounded-lg"
                                                >
                                                    <Link
                                                        :href="InternalDispatchController.show(company.id).url"
                                                        class="flex items-center"
                                                    >
                                                        <RiEyeLine class="h-4 w-4" />
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
                                        Try adjusting your search term.
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
        </div>
    </AppLayout>
</template>
