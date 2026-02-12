<script setup lang="ts">
/* ======================================================
   Component Imports (Dialogs & Shared UI)
====================================================== */

// Dialogs for CRUD actions
import ArchiveCompanyDialog from '@/components/company/ArchiveCompanyDialog.vue';
import CreateCompanyDialog from '@/components/company/CreateCompanyDialog.vue';
import EditCompanyDialog from '@/components/company/EditCompanyDialog.vue';
import { can } from '@/lib/can';
// Shared components
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';

// shadcn-vue UI components
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

// Wayfinder routes (type-safe route helpers)
import { index, show, trash } from '@/routes/companies';

// Breadcrumb type
import { type BreadcrumbItem } from '@/types';

// Inertia helpers
import { Head, Link } from '@inertiajs/vue3';

/* ======================================================
   Icons
====================================================== */

import {
    Archive,
    ArchiveX,
    Download,
    Edit,
    Eye,
    Plus,
    Upload,
} from 'lucide-vue-next';

/* ======================================================
   Vue Core
====================================================== */

import { ref } from 'vue';

/* ======================================================
   Types
====================================================== */

type Company = {
    id: number;
    company_name: string;
    created_at_human?: string;
};

/* ======================================================
   Breadcrumbs
====================================================== */

// Breadcrumbs shown in AppLayout
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
];

/* ======================================================
   Props from Inertia Controller
====================================================== */

const props = defineProps<{
    companies: any;
    filters: { search: string | null };
}>();

/* ======================================================
   Dialog State
====================================================== */

// Create dialog open state
const createOpen = ref(false);

// Edit dialog open state
const editOpen = ref(false);

// Archive dialog open state
const archiveOpen = ref(false);

// Currently selected company for edit/archive
const selectedCompany = ref<Company | null>(null);

/* ======================================================
   Actions
====================================================== */

// Open edit dialog and set selected company
function openEdit(company: Company) {
    selectedCompany.value = company;
    editOpen.value = true;
}

// Open archive dialog and set selected company
function openArchive(company: Company) {
    selectedCompany.value = company;
    archiveOpen.value = true;
}
</script>

<template>
    <!-- Page title (used by browser + Inertia) -->
    <Head title="Companies" />

    <!-- Main application layout -->
    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- ======================================================
                 Companies Card
            ======================================================= -->
            <Card class="mx-5">
                <!-- Card Header -->
                <CardHeader>
                    <CardTitle>Companies</CardTitle>
                    <CardDescription>
                        List of all companies in the system.
                    </CardDescription>

                    <!-- Header Actions -->
                    <CardAction>
                        <!-- View Archived Companies -->
                        <Button
                            v-if="can('company.viewAny')"
                            as-child
                            size="sm"
                            variant="outline"
                            class="mr-2"
                        >
                            <Link :href="trash().url">
                                <Archive class="mr-2 h-4 w-4" />
                                View Archived
                            </Link>
                        </Button>

                        <!-- Open Create Company Dialog -->
                        <Button
                            size="sm"
                            @click="createOpen = true"
                            class="cursor-pointer"
                        >
                            <Plus class="mr-2 h-4 w-4" />
                            New Company
                        </Button>
                    </CardAction>
                </CardHeader>

                <!-- Card Content -->
                <CardContent class="space-y-4">
                    <!-- ======================================================
                         Search & Import / Export Row
                    ======================================================= -->
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <!-- Search Input -->
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="index().url"
                                :initial-value="filters.search"
                                placeholder="Search companies..."
                                :only="['companies', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>

                        <!-- Import / Export Buttons -->
                        <div class="flex gap-2 sm:justify-end">
                            <Button
                                class="cursor-pointer"
                                size="sm"
                                variant="outline"
                            >
                                <Upload class="mr-2 h-4 w-4" />
                                Import
                            </Button>

                            <Button
                                class="cursor-pointer"
                                size="sm"
                                variant="outline"
                            >
                                <Download class="mr-2 h-4 w-4" />
                                Export
                            </Button>
                        </div>
                    </div>

                    <!-- ======================================================
                         Companies Table
                    ======================================================= -->
                    <Table>
                        <TableCaption> List of companies. </TableCaption>

                        <!-- Table Header -->
                        <TableHeader>
                            <TableRow>
                                <TableHead>Company Name</TableHead>
                                <TableHead>Created At</TableHead>
                                <TableHead class="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="company in companies.data"
                                :key="company.id"
                            >
                                <TableCell class="capitalize">
                                    {{ company.company_name }}
                                </TableCell>

                                <TableCell>
                                    {{ company.created_at_human }}
                                </TableCell>

                                <TableCell class="text-right">
                                    <div
                                        class="flex flex-wrap justify-end gap-2"
                                    >
                                        <!-- View -->
                                        <Button
                                            v-if="can('company.view')"
                                            as-child
                                            size="sm"
                                            variant="ghost"
                                        >
                                            <Link
                                                :href="
                                                    show({
                                                        company: company.id,
                                                    }).url
                                                "
                                            >
                                                <Eye class="mr-2 h-4 w-4" />
                                                View
                                            </Link>
                                        </Button>

                                        <!-- Edit -->
                                        <Button
                                            class="cursor-pointer"
                                            size="sm"
                                            variant="default"
                                            @click="openEdit(company)"
                                        >
                                            <Edit class="mr-2 h-4 w-4" />
                                            Edit
                                        </Button>

                                        <!-- Archive -->
                                        <Button
                                            class="cursor-pointer"
                                            size="sm"
                                            variant="archive"
                                            @click="openArchive(company)"
                                        >
                                            <ArchiveX class="mr-2 h-4 w-4" />
                                            Archive
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="companies.data.length === 0">
                                <TableCell
                                    colspan="4"
                                    class="py-10 text-center text-muted-foreground"
                                >
                                    No companies found.
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

            <!-- Create Company -->
            <CreateCompanyDialog v-model:open="createOpen" />

            <!-- Edit Company -->
            <EditCompanyDialog
                v-if="selectedCompany"
                v-model:open="editOpen"
                :company="selectedCompany"
            />

            <!-- Archive Company -->
            <ArchiveCompanyDialog
                v-if="selectedCompany"
                v-model:open="archiveOpen"
                :company="selectedCompany"
            />
        </div>
    </AppLayout>
</template>
