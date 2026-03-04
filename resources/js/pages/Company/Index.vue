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
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

/* ======================================================
   Layout, Routing & Inertia
====================================================== */

import AppLayout from '@/layouts/AppLayout.vue';
import { create, edit, index, show, trash } from '@/routes/companies';
import { type BreadcrumbItem } from '@/types';
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

type CompanyStatus =
    | 'draft'
    | 'docs_completed'
    | 'for_verification'
    | 'verified'
    | 'needs_revision'
    | 'rejected'
    | null;

type Company = {
    id: number;
    company_name: string;
    company_code: string;
    company_email?: string | null;
    company_phone?: string | null;
    status?: CompanyStatus;
    created_at_human?: string | null;
};

/* ======================================================
   Breadcrumbs
====================================================== */

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
];

/* ======================================================
   Props
====================================================== */

defineProps<{
    companies: any;
    filters: { search: string | null };
}>();

/* ======================================================
   Dialog State
====================================================== */

const createOpen = ref(false);
const editOpen = ref(false);
const archiveOpen = ref(false);
const selectedCompany = ref<Company | null>(null);

/* ======================================================
   Actions
====================================================== */

function openEdit(company: Company) {
    selectedCompany.value = company;
    editOpen.value = true;
}

function openArchive(company: Company) {
    selectedCompany.value = company;
    archiveOpen.value = true;
}

/* ======================================================
   Status helpers (human readable + default badge variants)
====================================================== */

function humanizeStatus(status?: CompanyStatus): string {
    if (!status) return '—';

    const map: Record<string, string> = {
        draft: 'Draft',
        docs_completed: 'Documents Completed',
        for_verification: 'For Verification',
        verified: 'Verified',
        needs_revision: 'Needs Revision',
        rejected: 'Rejected',
    };

    return map[status] ?? status.replace(/_/g, ' ');
}

function statusBadgeVariant(
    status?: CompanyStatus,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'verified':
            return 'default'; // primary
        case 'docs_completed':
        case 'for_verification':
            return 'secondary'; // muted
        case 'rejected':
            return 'destructive'; // red
        case 'draft':
        case 'needs_revision':
        default:
            return 'outline'; // neutral
    }
}
</script>

<template>
    <Head title="Companies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card class="mx-5">
                <CardHeader>
                    <CardTitle>Companies</CardTitle>
                    <CardDescription
                        >List of all companies in the system.</CardDescription
                    >

                    <CardAction>
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

                        <Button variant="default" size="sm" as-child>
                            <Link :href="create().url">
                                <Plus class="mr-2 h-4 w-4" />
                                New Company
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="index().url"
                                :initial-value="filters.search"
                                placeholder="Search companies..."
                                :only="['companies', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>

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

                    <Table>
                        <TableCaption>List of companies.</TableCaption>

                        <TableHeader>
                            <TableRow>
                                <TableHead>Company Name</TableHead>
                                <TableHead>Company Code</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Phone</TableHead>
                                <TableHead>Status</TableHead>
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
                                    {{ company.company_code }}
                                </TableCell>

                                <TableCell class="lowercase">
                                    {{ company.company_email ?? '-' }}
                                </TableCell>

                                <TableCell>
                                    {{ company.company_phone ?? '-' }}
                                </TableCell>

                                <TableCell>
                                    <Badge
                                        :variant="
                                            statusBadgeVariant(
                                                company.status ?? null,
                                            )
                                        "
                                    >
                                        {{
                                            humanizeStatus(
                                                company.status ?? null,
                                            )
                                        }}
                                    </Badge>
                                </TableCell>

                                <TableCell>
                                    {{ company.created_at_human ?? '-' }}
                                </TableCell>

                                <TableCell class="text-right">
                                    <div
                                        class="flex flex-wrap justify-end gap-2"
                                    >
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

                                        <Button
                                            v-if="can('company.update')"
                                            size="sm"
                                            variant="default"
                                            as-child
                                        >
                                            <Link
                                                :href="edit(company.id).url"
                                                class="cursor-pointer"
                                            >
                                                <Edit class="mr-2 h-4 w-4" />
                                                Edit
                                            </Link>
                                        </Button>

                                        <Button
                                            v-if="can('company.delete')"
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
                                    colspan="7"
                                    class="py-10 text-center text-muted-foreground"
                                >
                                    No companies found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

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

            <!-- Dialogs (if you still use them) -->
            <CreateCompanyDialog v-model:open="createOpen" />

            <EditCompanyDialog
                v-if="selectedCompany"
                v-model:open="editOpen"
                :company="selectedCompany"
            />

            <ArchiveCompanyDialog
                v-if="selectedCompany"
                v-model:open="archiveOpen"
                :company="selectedCompany"
            />
        </div>
    </AppLayout>
</template>
