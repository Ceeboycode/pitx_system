<script setup lang="ts">
/* ======================================================
   Layout, Routing & Inertia
====================================================== */
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

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
    CardAction,
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

/* ======================================================
   Icons
====================================================== */
import {
    Building2,
    ClipboardList,
    Eye,
    Mail,
    Phone,
    Truck,
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

type PaginatedCompanies = {
    data: CompanyItem[];
    links: PaginationLink[];
};

/* ======================================================
   Props
====================================================== */
const props = defineProps<{
    filters: {
        search: string;
    };
    companies: PaginatedCompanies;
}>();

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
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <Truck class="h-5 w-5 text-blue-700" />
                            Dispatch Companies
                        </CardTitle>
                        <CardDescription class="mt-1">
                            Find a company and view its total dispatch records.
                        </CardDescription>
                    </div>

                    <CardAction>
                        <div class="w-full md:w-72">
                            <SearchInput
                                :route="InternalDispatchController.index().url"
                                input-name="search"
                                placeholder="Search company…"
                                :default-value="props.filters.search"
                            />
                        </div>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 hover:bg-muted/40">
                                    <TableHead class="pl-4 text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
                                        Company
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
                                        Code
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
                                        Contact
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
                                        Status
                                    </TableHead>
                                    <TableHead class="text-center text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
                                        Dispatches
                                    </TableHead>
                                    <TableHead class="pr-4 text-right text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow
                                    v-for="company in companies.data"
                                    :key="company.id"
                                    class="transition-colors hover:bg-muted/30"
                                >
                                    <!-- Company -->
                                    <TableCell class="pl-4">
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

                                    <!-- Code -->
                                    <TableCell>
                                        <span
                                            v-if="company.company_code"
                                            class="rounded bg-muted px-2 py-0.5 font-mono text-xs font-semibold"
                                        >
                                            {{ company.company_code }}
                                        </span>
                                        <span v-else class="text-sm text-muted-foreground">—</span>
                                    </TableCell>

                                    <!-- Contact -->
                                    <TableCell>
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

                                    <!-- Status -->
                                    <TableCell>
                                        <Badge :class="['gap-1.5', statusClass(company.status)]">
                                            <span :class="['h-1.5 w-1.5 rounded-full', statusDot(company.status)]" />
                                            {{ prettyStatus(company.status) }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Dispatches count -->
                                    <TableCell class="text-center">
                                        <div class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">
                                            <ClipboardList class="h-3.5 w-3.5" />
                                            {{ company.dispatches_count }}
                                        </div>
                                    </TableCell>

                                    <!-- Action -->
                                    <TableCell class="pr-4 text-right">
                                        <TooltipProvider>
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Button
                                                        as-child
                                                        size="icon"
                                                        class="h-8 w-8 rounded-lg border-0 bg-blue-700 text-white hover:bg-blue-800"
                                                    >
                                                        <Link :href="InternalDispatchController.show(company.id).url">
                                                            <Eye class="h-3.5 w-3.5" />
                                                        </Link>
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent side="left">
                                                    View
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </TableCell>
                                </TableRow>

                                <!-- Empty state -->
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
                    />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>