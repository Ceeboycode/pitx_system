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
import { index as companyProfileChangeRequestsIndex } from '@/routes/company-profile-change-requests';
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
    Ellipsis,
    Mail,
    Phone,
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
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        Companies
                        <div class="ml-2 flex flex-1 items-center">
                            <hr class="h-px w-full border border-rose-500 " />
                            <div class="border-7 border-rose-500 rounded-xs">
                                <div class="border-3 border-white rounded-xs"></div>
                            </div>
                        </div>
                    </CardTitle>
                    <CardDescription class="mt-1">
                        Manage company records, review submissions, and
                        monitor verification status.
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="w-50/100">
                            <SearchInput
                                :route="index().url"
                                :initial-value="props.filters.search"
                                placeholder="Search companies…"
                                :only="['companies', 'filters', 'flash']"
                                :debounce="350"
                                class="shadow-sm rounded-lg "
                            />
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between min-w-50/100">
                            <div class="flex flex-wrap items-center gap-2">
                                <Select
                                    :model-value="statusFilter"
                                    @update:model-value="onStatusChange"
                                >
                                    <SelectTrigger
                                        class="h-9 w-fit cursor-pointer rounded-full border-0 bg-custom-bg px-3 text-custom-shadow shadow-none transition-all hover:-translate-y-0.5 hover:bg-custom-secondary/20 hover:shadow-md dark:bg-custom-bg-light dark:shadow-none dark:hover:bg-custom-secondary/20 dark:hover:inset-shadow-sm dark:hover:inset-shadow-white/5"
                                    >
                                        <Filter class="h-3.5 w-3.5" />
                                        <SelectValue placeholder="All Statuses" class="justify-start flex"/>
                                    </SelectTrigger>
                                    <SelectContent class="rounded-md shadow-lg">
                                        <SelectItem value="all" class="cursor-pointer text-sm hover:bg-custom-secondary/20"
                                            >All Statuses</SelectItem
                                        >
                                        <SelectItem value="draft" class="cursor-pointer text-sm hover:bg-custom-secondary/20"
                                            >Draft</SelectItem
                                        >
                                        <SelectItem
                                            value="docs_completed"
                                            class="cursor-pointer text-sm hover:bg-custom-secondary/20"
                                            >Docs Completed</SelectItem
                                        >
                                        <SelectItem
                                            value="for_verification"
                                            class="cursor-pointer text-sm hover:bg-custom-secondary/20"
                                            >For Verification</SelectItem
                                        >
                                        <SelectItem value="verified" class="cursor-pointer text-sm hover:bg-custom-secondary/20"
                                            >Verified</SelectItem
                                        >
                                        <SelectItem
                                            value="needs_revision"
                                            class="cursor-pointer text-sm hover:bg-custom-secondary/20"
                                            >Needs Revision</SelectItem
                                        >
                                        <SelectItem value="rejected" class="cursor-pointer text-sm hover:bg-custom-secondary/20"
                                            >Rejected</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between min-w-auto">
                                <div
                                    class="inline-flex gap-2"
                                >
                                    <Button
                                        variant="float-primary"
                                        size="icon-text"
                                        @click="importOpen = true"
                                    >
                                        <Download
                                            class="h-4 w-4 shrink-0"
                                        />
                                        <span
                                            class="hidden lg:inline"
                                        >
                                            Import
                                        </span>
                                    </Button>
                                    <div
                                        aria-hidden="true"
                                        class="hidden"
                                    />
                                    <Button
                                        variant="float"
                                        size="icon-text"
                                        :disabled="exporting"
                                        @click="triggerExport"
                                    >
                                        <Loader2
                                            v-if="exporting"
                                            class="h-4 w-4 shrink-0 animate-spin"
                                        />
                                        <Upload
                                            v-else
                                            class="h-4 w-4 shrink-0"
                                        />
                                        <span
                                            class="hidden lg:inline"
                                        >
                                            {{
                                                exporting
                                                    ? 'Exporting…'
                                                    : 'Export'
                                            }}
                                        </span>
                                    </Button>
                                </div>
                                <DropdownMenu
                                    v-if="
                                        canViewProfileChangeRequests ||
                                        canViewArchived
                                    "
                                    class="w-fit"
                                >
                                    <DropdownMenuTrigger as-child class="m-0">
                                        <div class="inline-flex">
                                            <Button
                                                variant="float"
                                                size="icon"
                                                aria-label="Open company actions"
                                            >
                                                <Ellipsis
                                                    class="h-4 w-4 shrink-0"
                                                />
                                            </Button>
                                        </div>
                                    </DropdownMenuTrigger>

                                    <DropdownMenuContent
                                        align="end"
                                        class="w-fit rounded-md shadow-lg"
                                    >
                                        <DropdownMenuItem
                                            v-if="canViewProfileChangeRequests"
                                            as-child
                                            class="cursor-pointer rounded-md text-custom-shadow transition-all hover:bg-custom-secondary/20 focus:bg-custom-secondary/20"
                                        >
                                            <Link
                                                :href="
                                                    companyProfileChangeRequestsIndex()
                                                        .url
                                                "
                                                class="flex items-center gap-2 px-2 py-1.5"
                                            >
                                                <ClipboardList
                                                    class="h-4 w-4"
                                                />
                                                Change Requests
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="canViewArchived"
                                            as-child
                                            class="cursor-pointer rounded-md text-custom-shadow transition-all hover:bg-custom-secondary/20 focus:bg-custom-secondary/20"
                                        >
                                            <Link
                                                :href="trash().url"
                                                class="flex items-center gap-2 px-2 py-1.5"
                                            >
                                                <Archive class="h-4 w-4" />
                                                Archives
                                            </Link>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader class="border-y border-slate-200">
                                <TableRow class="gap-2">
                                    <!-- Sortable: Company Name -->
                                    <TableHead
                                        class="px-0 cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
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
                                        class="px-0 cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
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
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Email</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Phone</TableHead
                                    >

                                    <!-- Sortable: Status -->
                                    <TableHead
                                        class="px-0 cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
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
                                        class="px-0 cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
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
                                        class="px-0 text-right text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Actions</TableHead
                                    >
                                </TableRow>
                            </TableHeader>

                            <TableBody class="border-y border-slate-200">
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
                                    <TableCell class="px-0">
                                        <p
                                            class="text-sm font-semibold capitalize"
                                        >
                                            {{ company.company_name }}
                                        </p>
                                    </TableCell>

                                    <!-- Code -->
                                    <TableCell class="px-0">
                                        <span
                                            class="rounded bg-muted px-2 py-0.5 font-mono text-xs font-semibold"
                                        >
                                            {{ company.company_code }}
                                        </span>
                                    </TableCell>

                                    <!-- Email -->
                                    <TableCell class="px-0">
                                        <div class="space-y-1">
                                            <!-- <p
                                                class="text-sm text-muted-foreground lowercase"
                                            >
                                                {{
                                                    company.company_email ?? '—'
                                                }}
                                            </p> -->
                                            <div class="flex items-center gap-1.5 text-muted-foreground">
                                                <Mail class="h-3.5 w-3.5 shrink-0" />
                                                <span class="truncate max-w-[180px]">
                                                    {{ company.company_email || '—' }}
                                                </span>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Phone -->
                                    <TableCell
                                        class="text-sm text-muted-foreground px-0"
                                    >
                                        <!-- {{ company.company_phone ?? '—' }} -->
                                        <div class="flex items-center gap-1.5 text-muted-foreground">
                                            <Phone class="h-3.5 w-3.5 shrink-0" />
                                            <span class="truncate max-w-[180px]">
                                                {{ company.company_phone || '—' }}
                                            </span>
                                        </div>
                                    </TableCell>

                                    <!-- Status -->
                                    <TableCell class="px-0">
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
                                        class="text-sm text-muted-foreground px-0"
                                    >
                                        {{ company.created_at_human ?? '—' }}
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="text-right px-0">
                                        <DropdownMenu v-if="canViewCompany">
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="float"
                                                    size="icon"
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
                                                class="w-fit rounded-md shadow-lg"
                                            >
                                                <DropdownMenuLabel
                                                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                                                >
                                                    {{ company.company_name }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    as-child
                                                    class="cursor-pointer rounded-md text-custom-shadow transition-all hover:bg-custom-secondary/20 focus:bg-custom-secondary/20"
                                                >
                                                    <Link
                                                        :href="
                                                            show({
                                                                company:
                                                                    company.id,
                                                            }).url
                                                        "
                                                        class="flex items-center gap-2 px-2 py-1.5"
                                                    >
                                                        <FileSearch
                                                            class="h-4 w-4"
                                                        />
                                                        Review Company
                                                        <!-- <ChevronRight
                                                            class="ml-auto h-3.5 w-3.5 text-blue-400"
                                                        /> -->
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
