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

/* ======================================================
   Icons
====================================================== */
import {
    Building2,
    ClipboardList,
    Eye,
    Mail,
    Phone,
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

function badgeVariant(status: string | null | undefined) {
    switch (status) {
        case 'verified':
        case 'active':
            return 'default';
        case 'pending':
            return 'secondary';
        default:
            return 'outline';
    }
}
</script>

<template>
    <Head title="Dispatches" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <Card class="rounded-2xl">
                <CardHeader class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <CardTitle class="text-2xl">Dispatch Companies</CardTitle>
                        <CardDescription>
                            Quickly find a company and view its total dispatch records.
                        </CardDescription>
                    </div>

                    <div class="w-full md:w-[320px]">
                        <SearchInput
                            :route="InternalDispatchController.index().url"
                            input-name="search"
                            placeholder="Search company..."
                            :default-value="props.filters.search"
                        />
                    </div>
                </CardHeader>

                <CardContent>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="min-w-[220px]">Company</TableHead>
                                    <TableHead class="min-w-[160px]">Code</TableHead>
                                    <TableHead class="min-w-[220px]">Contact</TableHead>
                                    <TableHead class="min-w-[140px]">Status</TableHead>
                                    <TableHead class="min-w-[160px] text-center">Total Dispatches</TableHead>
                                    <TableHead class="min-w-[140px] text-right">Action</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow
                                    v-for="company in companies.data"
                                    :key="company.id"
                                >
                                    <TableCell>
                                        <div class="flex items-start gap-3">
                                            <div class="rounded-lg bg-muted p-2">
                                                <Building2 class="h-4 w-4 text-muted-foreground" />
                                            </div>

                                            <div class="space-y-1">
                                                <div class="font-medium">
                                                    {{ company.company_name }}
                                                </div>
                                                <div class="text-xs text-muted-foreground">
                                                    Company ID: {{ company.id }}
                                                </div>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        {{ company.company_code || '—' }}
                                    </TableCell>

                                    <TableCell>
                                        <div class="space-y-1 text-sm">
                                            <div class="flex items-center gap-2 text-muted-foreground">
                                                <Mail class="h-4 w-4" />
                                                <span>{{ company.company_email || '—' }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-muted-foreground">
                                                <Phone class="h-4 w-4" />
                                                <span>{{ company.company_phone || '—' }}</span>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <Badge :variant="badgeVariant(company.status)">
                                            {{ prettyStatus(company.status) }}
                                        </Badge>
                                    </TableCell>

                                    <TableCell class="text-center">
                                        <div class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-sm font-medium">
                                            <ClipboardList class="h-4 w-4 text-muted-foreground" />
                                            <span>{{ company.dispatches_count }}</span>
                                        </div>
                                    </TableCell>

                                    <TableCell class="text-right">
                                        <Button as-child variant="outline" class="gap-2">
                                            <Link :href="InternalDispatchController.show(company.id).url">
                                                <Eye class="h-4 w-4" />
                                                View
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>

                                <TableRow v-if="companies.data.length === 0">
                                    <TableCell
                                        colspan="6"
                                        class="py-10 text-center text-muted-foreground"
                                    >
                                        No companies found.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div v-if="companies.links?.length" class="mt-6">
                        <InertiaPagination :links="companies.links" />
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
