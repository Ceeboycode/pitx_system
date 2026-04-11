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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import {
    Popover,
    PopoverTrigger,
    PopoverContent,
} from '@/components/ui/popover';
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
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Building2,
    ClipboardList,
    Eye,
    Mail,
    Phone,
    Truck,
    MoreHorizontal,
} from 'lucide-vue-next';

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
    if (sortBy.value !== field) return ArrowUpDown;
    return sortDir.value === 'asc' ? ArrowUp : ArrowDown;
}

function sortIconClass(field: SortField) {
    return sortBy.value === field ? 'text-blue-600' : 'text-muted-foreground/40';
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
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        Dispatches
                        <div class="ml-2 flex flex-1 items-center">
                            <hr class="h-px w-full border border-rose-500" />
                            <div class="rounded-xs border-7 border-rose-500">
                                <div class="rounded-xs border-3 border-white"></div>
                            </div>
                        </div>
                    </CardTitle>
                    <CardDescription class="mt-1">
                        Find a company and view its total dispatch records.
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="w-50/100">
                            <SearchInput
                                :route="InternalDispatchController.index().url"
                                input-name="search"
                                placeholder="Search company..."
                                :default-value="props.filters.search"
                                class="rounded-lg shadow-sm"
                            />
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader class="border-y border-slate-200">
                                <TableRow class="gap-2">
                                    <TableHead
                                        class="px-0 cursor-pointer select-none text-[11px] font-bold uppercase tracking-widest text-muted-foreground hover:text-foreground"
                                        @click="toggleSort('company_name')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Company
                                            <component :is="sortIcon('company_name')" class="h-3.5 w-3.5" :class="sortIconClass('company_name')" />
                                        </div>
                                    </TableHead>
                                    <TableHead
                                        class="px-0 cursor-pointer select-none text-[11px] font-bold uppercase tracking-widest text-muted-foreground hover:text-foreground"
                                        @click="toggleSort('company_code')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Code
                                            <component :is="sortIcon('company_code')" class="h-3.5 w-3.5" :class="sortIconClass('company_code')" />
                                        </div>
                                    </TableHead>
                                    <TableHead class="px-0 text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
                                        Contact
                                    </TableHead>
                                    <TableHead
                                        class="px-0 cursor-pointer select-none text-[11px] font-bold uppercase tracking-widest text-muted-foreground hover:text-foreground"
                                        @click="toggleSort('status')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Status
                                            <component :is="sortIcon('status')" class="h-3.5 w-3.5" :class="sortIconClass('status')" />
                                        </div>
                                    </TableHead>
                                    <TableHead
                                        class="px-0 cursor-pointer select-none text-center text-[11px] font-bold uppercase tracking-widest text-muted-foreground hover:text-foreground"
                                        @click="toggleSort('dispatches_count')"
                                    >
                                        <div class="flex items-center justify-center gap-1.5">
                                            Dispatches
                                            <component :is="sortIcon('dispatches_count')" class="h-3.5 w-3.5" :class="sortIconClass('dispatches_count')" />
                                        </div>
                                    </TableHead>
                                    <TableHead class="px-0 text-right text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody class="border-y border-slate-200">
                                <TableRow
                                    v-for="company in companies.data"
                                    :key="company.id"
                                    class="group transition-colors hover:bg-muted/30"
                                >
                                    <TableCell class="px-0">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 ring-1 ring-blue-100">
                                                <Building2 class="h-4 w-4 text-blue-600" />
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold">
                                                    {{ company.company_name }}
                                                </div>
                                                <div class="text-xs text-muted-foreground">
                                                    ID #{{ company.id }}
                                                </div>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <TableCell class="px-0">
                                        <span
                                            v-if="company.company_code"
                                            class="rounded bg-muted px-2 py-0.5 font-mono text-xs font-semibold"
                                        >
                                            {{ company.company_code }}
                                        </span>
                                        <span v-else class="text-sm text-muted-foreground">—</span>
                                    </TableCell>

                                    <TableCell class="px-0">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                                <Mail class="h-3.5 w-3.5 shrink-0" />
                                                <span class="truncate max-w-[180px]">
                                                    {{ company.company_email || '—' }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                                <Phone class="h-3.5 w-3.5 shrink-0" />
                                                <span>{{ company.company_phone || '—' }}</span>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <TableCell class="px-0">
                                        <Badge :class="['gap-1.5', statusClass(company.status)]">
                                            <span :class="['h-1.5 w-1.5 rounded-full', statusDot(company.status)]" />
                                            {{ prettyStatus(company.status) }}
                                        </Badge>
                                    </TableCell>

                                    <TableCell class="text-center px-0">
                                        <div class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">
                                            <ClipboardList class="h-3.5 w-3.5" />
                                            {{ company.dispatches_count }}
                                        </div>
                                    </TableCell>

                                    <TableCell class="px-0 text-right">
                                        <DropdownMenu
                                        >
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
                                                    {{ company.company_name }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    as-child
                                                    class="rounded-lg cursor-pointer hover:bg-slate-100"
                                                >
                                                    <Link
                                                        :href="InternalDispatchController.show(company.id).url"
                                                        class="flex items-center"
                                                    >
                                                        <Eye
                                                            class="h-4 w-4"
                                                        />
                                                        View
                                                    </Link>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>

                                <TableRow v-if="companies.data.length === 0" class="hover:bg-transparent">
                                    <TableCell colspan="6" class="py-20 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                                                <Truck class="h-6 w-6 text-muted-foreground/40" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-foreground">No companies found</p>
                                                <p class="mt-0.5 text-xs text-muted-foreground">
                                                    Try adjusting your search term.
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

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
