<script setup lang="ts">
import ArchiveCompanyDialog from '@/components/company/ArchiveCompanyDialog.vue';
import CreateCompanyDialog from '@/components/company/CreateCompanyDialog.vue';
import EditCompanyDialog from '@/components/company/EditCompanyDialog.vue';
import ImportCompanyDialog from '@/components/company/ImportCompanyDialog.vue';
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import { can } from '@/lib/can';

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
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCaption,
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

import AppLayout from '@/layouts/AppLayout.vue';
import { index, show, trash } from '@/routes/companies';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

import {
    Archive,
    ChevronRight,
    Download,
    FileSearch,
    Loader2,
    MoreHorizontal,
    Pencil,
    Upload,
} from 'lucide-vue-next';

import { ref } from 'vue';

/* ── Types ─────────────────────────────────────────────────────────────── */

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

/* ── Breadcrumbs ───────────────────────────────────────────────────────── */

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
];

/* ── Props ─────────────────────────────────────────────────────────────── */

defineProps<{
    companies: {
        data: Company[];
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: {
        search: string | null;
    };
}>();

/* ── Dialog state ──────────────────────────────────────────────────────── */

const createOpen = ref(false);
const editOpen = ref(false);
const archiveOpen = ref(false);
const importOpen = ref(false);
const selectedCompany = ref<Company | null>(null);

function openEdit(company: Company) {
    selectedCompany.value = company;
    editOpen.value = true;
}

function openArchive(company: Company) {
    selectedCompany.value = company;
    archiveOpen.value = true;
}

/* ── Export ────────────────────────────────────────────────────────────── */

const exporting = ref(false);

function triggerExport() {
    exporting.value = true;

    const a = document.createElement('a');
    a.href = '/companies/export';
    a.style.display = 'none';

    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    setTimeout(() => {
        exporting.value = false;
    }, 2000);
}

/* ── Import done ───────────────────────────────────────────────────────── */

function onImportDone() {
    router.reload({ only: ['companies'] });
}

/* ── Status helpers ────────────────────────────────────────────────────── */

function humanizeStatus(status?: CompanyStatus): string {
    if (!status) return '—';

    const map: Record<Exclude<CompanyStatus, null>, string> = {
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
            return 'default';

        case 'docs_completed':
        case 'for_verification':
            return 'secondary';

        case 'rejected':
            return 'destructive';

        default:
            return 'outline';
    }
}
</script>

<template>
    <Head title="Companies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-5">
                <CardHeader>
                    <CardTitle>Companies</CardTitle>
                    <CardDescription>
                        Manage company records, review submissions, and monitor verification status.
                    </CardDescription>

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
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
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
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button
                                            class="cursor-pointer"
                                            size="sm"
                                            variant="outline"
                                            @click="importOpen = true"
                                        >
                                            <Upload class="mr-2 h-4 w-4" />
                                            Import
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        Restore companies from a backup ZIP
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>

                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button
                                            class="cursor-pointer"
                                            size="sm"
                                            variant="outline"
                                            :disabled="exporting"
                                            @click="triggerExport"
                                        >
                                            <Loader2
                                                v-if="exporting"
                                                class="mr-2 h-4 w-4 animate-spin"
                                            />
                                            <Download
                                                v-else
                                                class="mr-2 h-4 w-4"
                                            />
                                            {{ exporting ? 'Exporting…' : 'Export' }}
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        Download all companies as a backup ZIP
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
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
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="company in companies.data"
                                :key="company.id"
                                class="group"
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
                                    <Badge :variant="statusBadgeVariant(company.status ?? null)">
                                        {{ humanizeStatus(company.status ?? null) }}
                                    </Badge>
                                </TableCell>

                                <TableCell>
                                    {{ company.created_at_human ?? '-' }}
                                </TableCell>

                                <TableCell class="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="h-8 w-8"
                                            >
                                                <MoreHorizontal class="h-4 w-4" />
                                                <span class="sr-only">Open actions</span>
                                            </Button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end" class="w-52">
                                            <DropdownMenuLabel
                                                class="text-xs font-normal text-muted-foreground"
                                            >
                                                {{ company.company_name }}
                                            </DropdownMenuLabel>

                                            <DropdownMenuSeparator />

                                            <DropdownMenuItem
                                                v-if="can('company.view')"
                                                as-child
                                            >
                                                <Link
                                                    :href="show({ company: company.id }).url"
                                                    class="flex items-center"
                                                >
                                                    <FileSearch class="mr-2 h-4 w-4" />
                                                    Review Company
                                                    <ChevronRight
                                                        class="ml-auto h-3.5 w-3.5 text-muted-foreground"
                                                    />
                                                </Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-if="can('company.update')"
                                                @click="openEdit(company)"
                                            >
                                                <Pencil class="mr-2 h-4 w-4" />
                                                Edit Company
                                            </DropdownMenuItem>

                                            <DropdownMenuSeparator v-if="can('company.delete')" />

                                            <DropdownMenuItem
                                                v-if="can('company.delete')"
                                                class="text-destructive focus:text-destructive"
                                                @click="openArchive(company)"
                                            >
                                                <Archive class="mr-2 h-4 w-4" />
                                                Archive Company
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
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
        </div>

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

        <ImportCompanyDialog
            v-model:open="importOpen"
            @done="onImportDone"
        />
    </AppLayout>
</template>
