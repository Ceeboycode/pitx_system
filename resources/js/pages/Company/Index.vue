<script setup lang="ts">
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Building2,
    ChevronRight,
    ClipboardList,
    Download,
    FileSearch,
    Filter,
    Loader2,
    MailCheck,
    MailX,
    MoreHorizontal,
    Upload,
    X,
} from 'lucide-vue-next';

import { computed, ref } from 'vue';

/* ── Types ──────────────────────────────────────────────────────── */

type CompanyStatus =
    | 'draft'
    | 'docs_completed'
    | 'for_verification'
    | 'verified'
    | 'needs_revision'
    | 'rejected'
    | null;

type SortField =
    | 'company_name'
    | 'company_code'
    | 'status'
    | 'created_at'
    | null;
type SortDir = 'asc' | 'desc';

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
    filters: {
        search: string | null;
        status: string | null;
        sort_by: SortField;
        sort_dir: SortDir;
    };
}>();

/* ── Permissions ─────────────────────────────────────────────────── */

const canViewArchived = computed(() => can('companies.viewAny'));
const canViewCompany = computed(() => can('companies.view'));
const canViewProfileChangeRequests = computed(() => can('companies.viewAny'));

/* ── Dialog state ────────────────────────────────────────────────── */

const createOpen = ref(false);
const editOpen = ref(false);
const importOpen = ref(false);
const selectedCompany = ref<Company | null>(null);

function openEdit(company: Company) {
    selectedCompany.value = company;
    editOpen.value = true;
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

/* ── Filter & Sort state ─────────────────────────────────────────── */

const statusFilter = ref<string>(props.filters.status ?? 'all');
const sortBy = ref<SortField>(props.filters.sort_by ?? null);
const sortDir = ref<SortDir>(props.filters.sort_dir ?? 'asc');

const hasActiveFilters = computed(
    () =>
        (statusFilter.value && statusFilter.value !== 'all') ||
        sortBy.value !== null,
);

function applyFilters(overrides: Record<string, string | null> = {}) {
    router.get(
        index().url,
        {
            search: props.filters.search ?? undefined,
            status:
                statusFilter.value !== 'all' ? statusFilter.value : undefined,
            sort_by: sortBy.value ?? undefined,
            sort_dir: sortBy.value ? sortDir.value : undefined,
            ...overrides,
        },
        {
            preserveState: true,
            replace: true,
            only: ['companies', 'filters', 'flash'],
        },
    );
}

function onStatusChange(val: string) {
    statusFilter.value = val;
    applyFilters();
}

function toggleSort(field: SortField) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDir.value = 'asc';
    }
    applyFilters();
}

function clearFilters() {
    statusFilter.value = 'all';
    sortBy.value = null;
    sortDir.value = 'asc';
    applyFilters({
        status: undefined,
        sort_by: undefined,
        sort_dir: undefined,
    });
}

/* ── Sort icon helper ────────────────────────────────────────────── */

function sortIcon(field: SortField) {
    if (sortBy.value !== field) return ArrowUpDown;
    return sortDir.value === 'asc' ? ArrowUp : ArrowDown;
}

function sortIconClass(field: SortField) {
    return sortBy.value === field
        ? 'text-blue-600'
        : 'text-muted-foreground/40';
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
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card class="mx-5">
                <CardHeader>
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <Building2 class="h-5 w-5 text-blue-700" />
                            Companies
                        </CardTitle>
                        <CardDescription class="mt-1">
                            Manage company records, review submissions, and
                            monitor verification status.
                        </CardDescription>
                    </div>

                    <CardAction class="flex items-center gap-2">
                        <Button
                            v-if="canViewProfileChangeRequests"
                            as-child
                            size="sm"
                            variant="outline"
                            class="rounded-lg border-blue-200 text-blue-700 hover:bg-blue-50"
                        >
                            <Link href="/company-profile-change-requests">
                                <ClipboardList class="mr-2 h-4 w-4" />
                                Profile Requests
                            </Link>
                        </Button>

                        <Button
                            v-if="canViewArchived"
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
                    <!-- Row 1: Search + Import/Export -->
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
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
                                    <TooltipContent
                                        >Restore companies from a backup
                                        ZIP</TooltipContent
                                    >
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
                                            <Loader2
                                                v-if="exporting"
                                                class="mr-2 h-4 w-4 animate-spin"
                                            />
                                            <Download
                                                v-else
                                                class="mr-2 h-4 w-4"
                                            />
                                            {{
                                                exporting
                                                    ? 'Exporting…'
                                                    : 'Export'
                                            }}
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent
                                        >Download all companies as a backup
                                        ZIP</TooltipContent
                                    >
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                    </div>

                    <!-- Row 2: Filters + Sort -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Filter icon label -->
                        <div
                            class="flex items-center gap-1.5 text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            <Filter class="h-3.5 w-3.5" />
                            Filter
                        </div>

                        <!-- Status filter -->
                        <Select
                            :model-value="statusFilter"
                            @update:model-value="onStatusChange"
                        >
                            <SelectTrigger
                                class="h-8 w-44 rounded-lg border-slate-200 text-xs"
                            >
                                <SelectValue placeholder="All Statuses" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="all" class="text-xs"
                                    >All Statuses</SelectItem
                                >
                                <SelectItem value="draft" class="text-xs"
                                    >Draft</SelectItem
                                >
                                <SelectItem
                                    value="docs_completed"
                                    class="text-xs"
                                    >Docs Completed</SelectItem
                                >
                                <SelectItem
                                    value="for_verification"
                                    class="text-xs"
                                    >For Verification</SelectItem
                                >
                                <SelectItem value="verified" class="text-xs"
                                    >Verified</SelectItem
                                >
                                <SelectItem
                                    value="needs_revision"
                                    class="text-xs"
                                    >Needs Revision</SelectItem
                                >
                                <SelectItem value="rejected" class="text-xs"
                                    >Rejected</SelectItem
                                >
                            </SelectContent>
                        </Select>

                        <!-- Sort by -->
                        <div
                            class="ml-2 flex items-center gap-1.5 text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            <ArrowUpDown class="h-3.5 w-3.5" />
                            Sort
                        </div>

                        <Select
                            :model-value="sortBy ?? 'none'"
                            @update:model-value="
                                (val) => {
                                    sortBy =
                                        val === 'none'
                                            ? null
                                            : (val as SortField);
                                    applyFilters();
                                }
                            "
                        >
                            <SelectTrigger
                                class="h-8 w-40 rounded-lg border-slate-200 text-xs"
                            >
                                <SelectValue placeholder="Sort by…" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="none" class="text-xs"
                                    >No Sort</SelectItem
                                >
                                <SelectItem value="company_name" class="text-xs"
                                    >Company Name</SelectItem
                                >
                                <SelectItem value="company_code" class="text-xs"
                                    >Company Code</SelectItem
                                >
                                <SelectItem value="status" class="text-xs"
                                    >Status</SelectItem
                                >
                                <SelectItem value="created_at" class="text-xs"
                                    >Created Date</SelectItem
                                >
                            </SelectContent>
                        </Select>

                        <!-- Sort direction toggle — only shown when a sort field is active -->
                        <Button
                            v-if="sortBy"
                            size="sm"
                            variant="outline"
                            class="h-8 rounded-lg border-slate-200 px-3 text-xs text-slate-600 hover:bg-slate-100"
                            @click="
                                sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                                applyFilters();
                            "
                        >
                            <ArrowUp
                                v-if="sortDir === 'asc'"
                                class="mr-1.5 h-3.5 w-3.5 text-blue-600"
                            />
                            <ArrowDown
                                v-else
                                class="mr-1.5 h-3.5 w-3.5 text-blue-600"
                            />
                            {{ sortDir === 'asc' ? 'Ascending' : 'Descending' }}
                        </Button>

                        <!-- Active filter badge + clear -->
                        <div
                            v-if="hasActiveFilters"
                            class="ml-auto flex items-center gap-2"
                        >
                            <Badge
                                class="gap-1 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700 hover:bg-blue-50"
                            >
                                <Filter class="h-3 w-3" />
                                Filters active
                            </Badge>
                            <Button
                                size="sm"
                                variant="ghost"
                                class="h-7 rounded-lg px-2 text-xs text-muted-foreground hover:text-rose-600"
                                @click="clearFilters"
                            >
                                <X class="mr-1 h-3.5 w-3.5" />
                                Clear
                            </Button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 hover:bg-muted/40">
                                    <!-- Sortable: Company Name -->
                                    <TableHead
                                        class="cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
                                        @click="toggleSort('company_name')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Company Name
                                            <component
                                                :is="sortIcon('company_name')"
                                                class="h-3.5 w-3.5"
                                                :class="
                                                    sortIconClass(
                                                        'company_name',
                                                    )
                                                "
                                            />
                                        </div>
                                    </TableHead>

                                    <!-- Sortable: Code -->
                                    <TableHead
                                        class="cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
                                        @click="toggleSort('company_code')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Code
                                            <component
                                                :is="sortIcon('company_code')"
                                                class="h-3.5 w-3.5"
                                                :class="
                                                    sortIconClass(
                                                        'company_code',
                                                    )
                                                "
                                            />
                                        </div>
                                    </TableHead>

                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Email</TableHead
                                    >
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Phone</TableHead
                                    >

                                    <!-- Sortable: Status -->
                                    <TableHead
                                        class="cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
                                        @click="toggleSort('status')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Status
                                            <component
                                                :is="sortIcon('status')"
                                                class="h-3.5 w-3.5"
                                                :class="sortIconClass('status')"
                                            />
                                        </div>
                                    </TableHead>

                                    <!-- Sortable: Created -->
                                    <TableHead
                                        class="cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
                                        @click="toggleSort('created_at')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Created
                                            <component
                                                :is="sortIcon('created_at')"
                                                class="h-3.5 w-3.5"
                                                :class="
                                                    sortIconClass('created_at')
                                                "
                                            />
                                        </div>
                                    </TableHead>

                                    <TableHead
                                        class="text-right text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Actions</TableHead
                                    >
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <!-- Empty state -->
                                <TableRow
                                    v-if="props.companies.data.length === 0"
                                    class="hover:bg-transparent"
                                >
                                    <TableCell
                                        colspan="7"
                                        class="py-20 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center gap-3"
                                        >
                                            <div
                                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted"
                                            >
                                                <Building2
                                                    class="h-6 w-6 text-muted-foreground/40"
                                                />
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-foreground"
                                                >
                                                    No companies found
                                                </p>
                                                <p
                                                    class="mt-0.5 text-xs text-muted-foreground"
                                                >
                                                    {{
                                                        hasActiveFilters
                                                            ? 'Try adjusting your filters or search.'
                                                            : 'Try adjusting your search.'
                                                    }}
                                                </p>
                                            </div>
                                            <Button
                                                v-if="hasActiveFilters"
                                                size="sm"
                                                variant="outline"
                                                class="mt-1 h-8 rounded-lg text-xs"
                                                @click="clearFilters"
                                            >
                                                <X class="mr-1.5 h-3.5 w-3.5" />
                                                Clear filters
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="company in props.companies.data"
                                    :key="company.id"
                                    class="group transition-colors hover:bg-muted/30"
                                >
                                    <!-- Company Name -->
                                    <TableCell>
                                        <p
                                            class="text-sm font-semibold capitalize"
                                        >
                                            {{ company.company_name }}
                                        </p>
                                    </TableCell>

                                    <!-- Code -->
                                    <TableCell>
                                        <span
                                            class="rounded bg-muted px-2 py-0.5 font-mono text-xs font-semibold"
                                        >
                                            {{ company.company_code }}
                                        </span>
                                    </TableCell>

                                    <!-- Email -->
                                    <TableCell>
                                        <div class="space-y-1">
                                            <p
                                                class="text-sm text-muted-foreground lowercase"
                                            >
                                                {{
                                                    company.company_email ?? '—'
                                                }}
                                            </p>
                                            <div
                                                v-if="company.company_email"
                                                class="flex items-center gap-1.5"
                                            >
                                                <Badge
                                                    :class="
                                                        hasVerifiedEmail(
                                                            company,
                                                        )
                                                            ? 'gap-1 border-emerald-200 bg-emerald-100 text-[10px] text-emerald-700'
                                                            : 'gap-1 border-0 bg-slate-100 text-[10px] text-slate-500'
                                                    "
                                                >
                                                    <MailCheck
                                                        v-if="
                                                            hasVerifiedEmail(
                                                                company,
                                                            )
                                                        "
                                                        class="h-3 w-3"
                                                    />
                                                    <MailX
                                                        v-else
                                                        class="h-3 w-3"
                                                    />
                                                    {{
                                                        hasVerifiedEmail(
                                                            company,
                                                        )
                                                            ? 'Verified'
                                                            : 'Not Verified'
                                                    }}
                                                </Badge>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Phone -->
                                    <TableCell
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{ company.company_phone ?? '—' }}
                                    </TableCell>

                                    <!-- Status -->
                                    <TableCell>
                                        <Badge
                                            :class="[
                                                'gap-1.5',
                                                statusClass(
                                                    company.status ?? null,
                                                ),
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'h-1.5 w-1.5 rounded-full',
                                                    statusDot(
                                                        company.status ?? null,
                                                    ),
                                                ]"
                                            />
                                            {{
                                                humanizeStatus(
                                                    company.status ?? null,
                                                )
                                            }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Created -->
                                    <TableCell
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{ company.created_at_human ?? '—' }}
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="text-right">
                                        <DropdownMenu v-if="canViewCompany">
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-8 w-8 rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground"
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
                                                class="w-52 rounded-xl border-slate-200 shadow-lg"
                                            >
                                                <DropdownMenuLabel
                                                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                                                >
                                                    {{ company.company_name }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    as-child
                                                    class="rounded-lg text-blue-700 focus:bg-blue-50 focus:text-blue-700"
                                                >
                                                    <Link
                                                        :href="
                                                            show({
                                                                company:
                                                                    company.id,
                                                            }).url
                                                        "
                                                        class="flex items-center"
                                                    >
                                                        <FileSearch
                                                            class="mr-2 h-4 w-4"
                                                        />
                                                        Review Company
                                                        <ChevronRight
                                                            class="ml-auto h-3.5 w-3.5 text-blue-400"
                                                        />
                                                    </Link>
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

        <ImportCompanyDialog v-model:open="importOpen" @done="onImportDone" />
    </AppLayout>
</template>
