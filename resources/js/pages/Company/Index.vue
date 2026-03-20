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
    Building2,
    ChevronRight,
    Download,
    FileSearch,
    Loader2,
    MailCheck,
    MailX,
    MoreHorizontal,
    Upload,
} from 'lucide-vue-next';

import { computed, ref } from 'vue';

/* ── Types ──────────────────────────────────────────────────────────── */

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
    company_email_verified_at?: string | null;
    company_phone?: string | null;
    status?: CompanyStatus;
    created_at_human?: string | null;
};

/* ── Breadcrumbs ─────────────────────────────────────────────────── */

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
];

/* ── Props ───────────────────────────────────────────────────────── */

const props = defineProps<{
    companies: {
        data: Company[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { search: string | null };
}>();

/* ── Permissions ─────────────────────────────────────────────────── */

const canViewArchived = computed(() => can('companies.viewAny'));
const canViewCompany = computed(() => can('companies.view'));
const canArchiveCompany = computed(() => can('companies.delete'));

/* create/update are not active in controller yet */
// const canCreateCompany = computed(() => can('companies.create'));
// const canEditCompany = computed(() => can('companies.update'));

/* ── Dialog state ────────────────────────────────────────────────── */

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

/* ── Export ──────────────────────────────────────────────────────── */

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

function onImportDone() {
    router.reload({ only: ['companies'] });
}

/* ── Status helpers ──────────────────────────────────────────────── */

function humanizeStatus(status?: CompanyStatus): string {
    if (!status) return '—';

    const map: Record<Exclude<CompanyStatus, null>, string> = {
        draft: 'Draft',
        docs_completed: 'Docs Completed',
        for_verification: 'For Verification',
        verified: 'Verified',
        needs_revision: 'Needs Revision',
        rejected: 'Rejected',
    };

    return map[status] ?? status.replace(/_/g, ' ');
}

function statusClass(status?: CompanyStatus): string {
    switch (status) {
        case 'verified':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'docs_completed':
            return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'for_verification':
            return 'bg-violet-100 text-violet-700 border-violet-200';
        case 'needs_revision':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'rejected':
            return 'bg-rose-100 text-rose-600 border-rose-200';
        case 'draft':
        default:
            return 'bg-slate-100 text-slate-500 border-0';
    }
}

function statusDot(status?: CompanyStatus): string {
    switch (status) {
        case 'verified':
            return 'bg-emerald-500';
        case 'docs_completed':
            return 'bg-blue-500';
        case 'for_verification':
            return 'bg-violet-500';
        case 'needs_revision':
            return 'bg-amber-500';
        case 'rejected':
            return 'bg-rose-500';
        default:
            return 'bg-slate-400';
    }
}

function hasVerifiedEmail(company: Company): boolean {
    return !!company.company_email_verified_at;
}
</script>

<template>
    <Head title="Companies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-5">
                <CardHeader>
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <Building2 class="h-5 w-5 text-blue-700" />
                            Companies
                        </CardTitle>
                        <CardDescription class="mt-1">
                            Manage company records, review submissions, and monitor verification status.
                        </CardDescription>
                    </div>

                    <CardAction v-if="canViewArchived" class="flex items-center gap-2">
                        <Button
                            as-child
                            size="sm"
                            variant="outline"
                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
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
                                :initial-value="props.filters.search"
                                placeholder="Search companies…"
                                :only="['companies', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>

                        <div class="flex gap-2 sm:justify-end">
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                                            @click="importOpen = true"
                                        >
                                            <Upload class="mr-2 h-4 w-4" />
                                            Import
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Restore companies from a backup ZIP</TooltipContent>
                                </Tooltip>
                            </TooltipProvider>

                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                                            :disabled="exporting"
                                            @click="triggerExport"
                                        >
                                            <Loader2 v-if="exporting" class="mr-2 h-4 w-4 animate-spin" />
                                            <Download v-else class="mr-2 h-4 w-4" />
                                            {{ exporting ? 'Exporting…' : 'Export' }}
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Download all companies as a backup ZIP</TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 hover:bg-muted/40">
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Company Name</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Code</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Email</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Phone</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Status</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Created</TableHead>
                                    <TableHead class="text-right text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Actions</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow v-if="props.companies.data.length === 0" class="hover:bg-transparent">
                                    <TableCell colspan="7" class="py-20 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                                                <Building2 class="h-6 w-6 text-muted-foreground/40" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-foreground">No companies found</p>
                                                <p class="mt-0.5 text-xs text-muted-foreground">Try adjusting your search.</p>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="company in props.companies.data"
                                    :key="company.id"
                                    class="group transition-colors hover:bg-muted/30"
                                >
                                    <TableCell>
                                        <p class="text-sm font-semibold capitalize">{{ company.company_name }}</p>
                                    </TableCell>

                                    <TableCell>
                                        <span class="rounded bg-muted px-2 py-0.5 font-mono text-xs font-semibold">
                                            {{ company.company_code }}
                                        </span>
                                    </TableCell>

                                    <TableCell>
                                        <div class="space-y-1">
                                            <p class="text-sm lowercase text-muted-foreground">
                                                {{ company.company_email ?? '—' }}
                                            </p>

                                            <div v-if="company.company_email" class="flex items-center gap-1.5">
                                                <Badge
                                                    :variant="hasVerifiedEmail(company) ? 'default' : 'outline'"
                                                    class="gap-1 text-[10px]"
                                                >
                                                    <MailCheck v-if="hasVerifiedEmail(company)" class="h-3 w-3" />
                                                    <MailX v-else class="h-3 w-3" />
                                                    {{ hasVerifiedEmail(company) ? 'Verified' : 'Not Verified' }}
                                                </Badge>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ company.company_phone ?? '—' }}
                                    </TableCell>

                                    <TableCell>
                                        <Badge :class="['gap-1.5', statusClass(company.status ?? null)]">
                                            <span :class="['h-1.5 w-1.5 rounded-full', statusDot(company.status ?? null)]" />
                                            {{ humanizeStatus(company.status ?? null) }}
                                        </Badge>
                                    </TableCell>

                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ company.created_at_human ?? '—' }}
                                    </TableCell>

                                    <TableCell class="text-right">
                                        <DropdownMenu v-if="canViewCompany || canArchiveCompany">
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-8 w-8 rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground"
                                                >
                                                    <MoreHorizontal class="h-4 w-4" />
                                                    <span class="sr-only">Open actions</span>
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end" class="w-52 rounded-xl border-slate-200 shadow-lg">
                                                <DropdownMenuLabel class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                                    {{ company.company_name }}
                                                </DropdownMenuLabel>

                                                <DropdownMenuSeparator v-if="canViewCompany" />

                                                <DropdownMenuItem
                                                    v-if="canViewCompany"
                                                    as-child
                                                    class="rounded-lg text-blue-700 focus:bg-blue-50 focus:text-blue-700"
                                                >
                                                    <Link :href="show({ company: company.id }).url" class="flex items-center">
                                                        <FileSearch class="mr-2 h-4 w-4" />
                                                        Review Company
                                                        <ChevronRight class="ml-auto h-3.5 w-3.5 text-blue-400" />
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuSeparator v-if="canArchiveCompany" />

                                                <DropdownMenuItem
                                                    v-if="canArchiveCompany"
                                                    class="rounded-lg text-rose-600 focus:bg-rose-50 focus:text-rose-600"
                                                    @click="openArchive(company)"
                                                >
                                                    <Archive class="mr-2 h-4 w-4" />
                                                    Archive Company
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <InertiaPagination
                        :links="props.companies.links"
                        :meta="{
                            from: props.companies.from,
                            to: props.companies.to,
                            total: props.companies.total,
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

        <ImportCompanyDialog v-model:open="importOpen" @done="onImportDone" />
    </AppLayout>
</template>
