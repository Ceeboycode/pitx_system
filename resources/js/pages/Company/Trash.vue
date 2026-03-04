<script setup lang="ts">
/* ======================================================
   Shared Components
====================================================== */

// Pagination component for Inertia responses
import InertiaPagination from '@/components/InertiaPagination.vue';

// Reusable debounced search input
import SearchInput from '@/components/SearchInput.vue';

// shadcn-vue button
import { Button } from '@/components/ui/button';

/* ======================================================
   Dialog Components
====================================================== */

// Dialog to permanently delete a company
import ForceDeleteCompanyDialog from '@/components/company/ForceDeleteCompanyDialog.vue';

// Dialog to restore an archived company
import RestoreCompanyDialog from '@/components/company/RestoreCompanyDialog.vue';

/* ======================================================
   shadcn-vue UI Components
====================================================== */

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
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

/* ======================================================
   Layout, Routing & Inertia
====================================================== */

// Main application layout
import AppLayout from '@/layouts/AppLayout.vue';

// Wayfinder routes
import { index, trash } from '@/routes/companies';

// Breadcrumb type
import { type BreadcrumbItem } from '@/types';

// Inertia helpers
import { Head, Link } from '@inertiajs/vue3';

/* ======================================================
   Icons
====================================================== */

import { ArrowLeft, RotateCcw, Trash2 } from 'lucide-vue-next';

/* ======================================================
   Vue Core
====================================================== */

import { ref } from 'vue';

/* ======================================================
   Types
====================================================== */

// Company shape for archived records
type Company = {
    id: number;
    company_name: string;
    company_code: string;
    deleted_at_human?: string;
    deleter?: { id: number; name: string } | null;
};

/* ======================================================
   Breadcrumbs
====================================================== */

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
    { title: 'Archived', href: trash().url },
];

/* ======================================================
   Props from Inertia Controller
====================================================== */

const props = defineProps<{
    companies: any; // Paginated archived companies
    filters: { search: string | null }; // Active search filter
}>();

/* ======================================================
   Dialog State
====================================================== */

// Restore dialog open state
const restoreOpen = ref(false);

// Force delete dialog open state
const forceDeleteOpen = ref(false);

// Currently selected company (restore / delete)
const selectedCompany = ref<Company | null>(null);

/* ======================================================
   Actions
====================================================== */

// Open restore dialog for selected company
function openRestore(company: Company) {
    selectedCompany.value = company;
    restoreOpen.value = true;
}

// Open force delete dialog for selected company
function openForceDelete(company: Company) {
    selectedCompany.value = company;
    forceDeleteOpen.value = true;
}
</script>

<template>
    <!-- Page title -->
    <Head title="Archived Companies" />

    <!-- Main application layout -->
    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- ======================================================
                 Archived Companies Card
            ======================================================= -->
            <Card class="mx-10">
                <!-- Card Header -->
                <CardHeader>
                    <CardTitle>Archived Companies</CardTitle>
                    <CardDescription>
                        Archived companies can be restored or permanently
                        deleted.
                    </CardDescription>

                    <!-- Header Action -->
                    <CardAction>
                        <!-- Back to active companies -->
                        <Button as-child size="sm" variant="link" class="mr-2">
                            <Link :href="index().url">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Back to Companies
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <!-- Card Content -->
                <CardContent class="space-y-4">
                    <!-- ======================================================
                         Search Row
                    ======================================================= -->
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <!-- Search archived companies -->
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="trash().url"
                                :initial-value="filters.search"
                                placeholder="Search archived companies..."
                                :only="['companies', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>
                    </div>

                    <!-- ======================================================
                         Archived Companies Table
                    ======================================================= -->
                    <Table>
                        <TableCaption>
                            List of archived companies.
                        </TableCaption>

                        <!-- Table Header -->
                        <TableHeader>
                            <TableRow>
                                <TableHead>Company Name</TableHead>
                                <TableHead>Company Code</TableHead>
                                <TableHead>Archived At</TableHead>
                                <TableHead>Archived By</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>

                        <!-- Table Body -->
                        <TableBody>
                            <!-- Company Rows -->
                            <TableRow
                                v-for="company in companies.data"
                                :key="company.id"
                            >
                                <TableCell class="capitalize">
                                    {{ company.company_name }}
                                </TableCell>

                                <TableCell class="capitalize">
                                    {{ company.company_code }}
                                </TableCell>

                                <TableCell>
                                    {{ company.deleted_at_human ?? '—' }}
                                </TableCell>

                                <TableCell class="capitalize">
                                    {{ company.deleter?.name ?? '—' }}
                                </TableCell>

                                <!-- Action Buttons -->
                                <TableCell class="space-x-2">
                                    <!-- Restore -->
                                    <Button
                                        class="cursor-pointer"
                                        size="sm"
                                        variant="secondary"
                                        @click="openRestore(company)"
                                    >
                                        <RotateCcw class="mr-2 h-4 w-4" />
                                        Restore
                                    </Button>

                                    <!-- Force Delete -->
                                    <Button
                                        class="cursor-pointer"
                                        size="sm"
                                        variant="destructive"
                                        @click="openForceDelete(company)"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Delete Permanently
                                    </Button>
                                </TableCell>
                            </TableRow>

                            <!-- Empty State -->
                            <TableRow v-if="companies.data.length === 0">
                                <TableCell
                                    colspan="4"
                                    class="py-10 text-center text-muted-foreground"
                                >
                                    No archived companies found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- ======================================================
                         Pagination
                    ======================================================= -->
                    <InertiaPagination
                        :links="companies.links"
                        :meta="{
                            from: companies.from,
                            to: companies.to,
                            total: companies.total,
                        }"
                    />
                </CardContent>
            </Card>

            <!-- ======================================================
                 Dialogs
            ======================================================= -->

            <!-- Restore Company Dialog -->
            <RestoreCompanyDialog
                v-if="selectedCompany"
                v-model:open="restoreOpen"
                :company="selectedCompany"
            />

            <!-- Force Delete Company Dialog -->
            <ForceDeleteCompanyDialog
                v-if="selectedCompany"
                v-model:open="forceDeleteOpen"
                :company="selectedCompany"
            />
        </div>
    </AppLayout>
</template>
