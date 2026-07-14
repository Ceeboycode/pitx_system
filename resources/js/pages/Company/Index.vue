<script setup lang="ts">
import CreateCompanyDialog from '@/components/company/CreateCompanyDialog.vue';
import EditCompanyDialog from '@/components/company/EditCompanyDialog.vue';
import ImportCompanyDialog from '@/components/company/ImportCompanyDialog.vue';
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import { can } from '@/lib/can';

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
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as companyProfileChangeRequestsIndex } from '@/routes/company-profile-change-requests';
import { index, show, trash } from '@/routes/companies';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

import {
    RiArchive2Line,
    RiArrowDownSLine,
    RiArrowUpDownLine,
    RiArrowUpSLine,
    RiClipboardLine,
    RiDownloadLine,
    RiFileSearchLine,
    RiFilter2Line,
    RiLoaderLine,
    RiMailLine,
    RiMore2Line,
    RiPhoneLine,
    RiUploadLine,
} from 'vue-remix-icons';

import { computed, ref } from 'vue';

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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
];

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

const canViewArchived = computed(() => can('companies.viewAny'));
const canViewCompany = computed(() => can('companies.view'));
const canViewProfileChangeRequests = computed(() => can('companies.viewAny'));

const createOpen = ref(false);
const editOpen = ref(false);
const importOpen = ref(false);
const selectedCompany = ref<Company | null>(null);

function openEdit(company: Company) {
    selectedCompany.value = company;
    editOpen.value = true;
}

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

const statusFilter = ref<string>(props.filters.status ?? 'all');
const sortBy = ref<SortField>(props.filters.sort_by ?? null);
const sortDir = ref<SortDir>(props.filters.sort_dir ?? 'asc');
const filterOpen = ref(false);

const hasActiveFilters = computed(
    () =>
        (statusFilter.value && statusFilter.value !== 'all') ||
        sortBy.value !== null,
);

const activeFilterCount = computed(() => {
    let count = 0;
    if (statusFilter.value && statusFilter.value !== 'all') count++;
    return count;
});

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

    filterOpen.value = false;
}

function onStatusChange(val: string) {
    statusFilter.value = val;
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

function sortIcon(field: SortField) {
    if (sortBy.value !== field) return RiArrowUpDownLine;
    return sortDir.value === 'asc' ? RiArrowUpSLine : RiArrowDownSLine;
}

function sortIconClass(field: SortField) {
    return sortBy.value === field
        ? 'text-custom-primary'
        : 'text-custom-shadow/40';
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
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4">
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row gap-2">
                    <div class="flex flex-col">
                        <CardTitle class="flex items-center gap-2">
                            <span class="font-semibold">Companies</span>
                        </CardTitle>
                        <CardDescription>
                            Manage company records, review submissions, and monitor verification status.
                        </CardDescription>
                    </div>
                    <div class="flex flex-1 justify-end gap-2">
                        <div class="lg:flex items-center gap-2 sm:justify-end">
                            <!-- <Button
                                variant="float-primary"
                                class="hidden lg:flex"
                                @click="importOpen = true"
                            >
                                <RiDownloadLine class="h-4 w-4 shrink-0" />
                                <span>Import</span>
                            </Button> -->

                            <!-- <Button
                                variant="float"
                                class="hidden lg:flex"
                                :disabled="exporting"
                                @click="triggerExport"
                            >
                                <RiLoaderLine
                                    v-if="exporting"
                                    class="h-4 w-4 shrink-0 animate-spin"
                                />
                                <RiUploadLine
                                    v-else
                                    class="h-4 w-4 shrink-0"
                                />
                                <span>{{ exporting ? 'Exporting...' : 'Export' }}</span>
                            </Button> -->

                            <DropdownMenu
                                v-if="canViewProfileChangeRequests || canViewArchived"
                                class="w-fit"
                            >
                                <DropdownMenuTrigger as-child class="m-0">
                                    <div class="inline-flex">
                                        <Button
                                            variant="header-actions"
                                            class="text-custom-shadow"
                                            size="icon"
                                            aria-label="Open company actions"
                                        >
                                            <RiMore2Line class="h-4 w-4 shrink-0" />
                                        </Button>
                                    </div>
                                </DropdownMenuTrigger>

                                <DropdownMenuContent align="end" class="w-fit">
                                    <DropdownMenuItem
                                        as-child
                                        class="cursor-pointer lg:hidden"
                                        @click="importOpen = true"
                                    >
                                        <button type="button" class="flex w-full items-center">
                                            <RiDownloadLine class="h-4 w-4" />
                                            Import
                                        </button>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        as-child
                                        class="cursor-pointer lg:hidden"
                                        @click="triggerExport"
                                    >
                                        <button type="button" class="flex w-full items-center">
                                            <RiUploadLine class="h-4 w-4" />
                                            Export
                                        </button>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-if="canViewProfileChangeRequests"
                                        as-child
                                        class="cursor-pointer"
                                    >
                                        <Link
                                            :href="companyProfileChangeRequestsIndex().url"
                                            class="flex items-center"
                                        >
                                            <RiClipboardLine class="h-4 w-4" />
                                            Change Requests
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuItem
                                        v-if="canViewArchived"
                                        as-child
                                        class="cursor-pointer"
                                    >
                                        <Link :href="trash().url" class="flex items-center">
                                            <RiArchive2Line class="h-4 w-4" />
                                            Archives
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                    <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                        <div class="w-full">
                            <SearchInput
                                :route="index().url"
                                :initial-value="props.filters.search"
                                placeholder="Search companies…"
                                :only="['companies', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>

                        <div class="flex w-fit flex-row gap-2 lg:items-center lg:justify-between">
                            <Popover v-model:open="filterOpen">
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="header-actions"
                                        size="icon-text"
                                        class="rounded-full"
                                        :class="
                                            activeFilterCount > 0
                                                ? 'bg-custom-secondary/20 hover:bg-custom-secondary/80 hover:text-custom-bg-light transition-all duration-300 dark:hover:text-custom-shadow'
                                                : ''
                                        "
                                    >
                                        <RiFilter2Line class="h-3.5 w-3.5" />
                                        <span class="hidden lg:flex">
                                            {{
                                                activeFilterCount > 0
                                                    ? (activeFilterCount === 1 ? '1 filter active' : `${activeFilterCount} filters active`)
                                                    : 'Filter'
                                            }}
                                        </span>
                                    </Button>
                                </PopoverTrigger>

                                <PopoverContent align="end">
                                    <div class="grid gap-y-2">
                                        <div class="space-y-2">
                                            <p class="text-sm text-custom-shadow/80">
                                                Status
                                            </p>
                                            <Select
                                                :model-value="statusFilter"
                                                @update:model-value="onStatusChange"
                                            >
                                                <SelectTrigger class="w-full">
                                                    <SelectValue placeholder="Any status" class="flex justify-start" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all" class="cursor-pointer">Any status</SelectItem>
                                                    <SelectItem value="draft" class="cursor-pointer">Draft</SelectItem>
                                                    <SelectItem value="docs_completed" class="cursor-pointer">Docs Completed</SelectItem>
                                                    <SelectItem value="for_verification" class="cursor-pointer">For Verification</SelectItem>
                                                    <SelectItem value="verified" class="cursor-pointer">Verified</SelectItem>
                                                    <SelectItem value="needs_revision" class="cursor-pointer">Needs Revision</SelectItem>
                                                    <SelectItem value="rejected" class="cursor-pointer">Rejected</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <hr class="my-1 h-px border-0 bg-custom-bg-dark">

                                        <div class="flex w-full flex-row items-center justify-between">
                                            <Button
                                                v-if="hasActiveFilters"
                                                size="sm"
                                                variant="destructive"
                                                @click="clearFilters"
                                            >
                                                Clear
                                            </Button>

                                            <div class="ml-auto flex items-center gap-2">
                                                <Button
                                                    variant="ghost-outline"
                                                    size="sm"
                                                    @click="filterOpen = false"
                                                >
                                                    Cancel
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="float-primary"
                                                    @click="applyFilters"
                                                >
                                                    Apply
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        </div>
                    </div>

                    <Card
                        :class="[
                            'flex min-h-0 flex-1 max-h-fit flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none',
                            props.companies.data.length === 0 ? 'border-dashed' : 'border-solid',
                        ]"
                    >
                        <div v-if="props.companies.data.length > 0" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                            <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                                <div class="grid grid-cols-7 gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                    <button
                                        type="button"
                                        class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 pl-3 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('company_name')"
                                    >
                                        Name
                                        <component
                                            :is="sortIcon('company_name')"
                                            class="h-3.5 w-3.5"
                                            :class="sortIconClass('company_name')"
                                        />
                                    </button>

                                    <button
                                        type="button"
                                        class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('company_code')"
                                    >
                                        Code
                                        <component
                                            :is="sortIcon('company_code')"
                                            class="h-3.5 w-3.5"
                                            :class="sortIconClass('company_code')"
                                        />
                                    </button>

                                    <div class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">
                                        Email
                                    </div>
                                    <div class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">
                                        Phone
                                    </div>

                                    <button
                                        type="button"
                                        class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('status')"
                                    >
                                        Status
                                        <component
                                            :is="sortIcon('status')"
                                            class="h-3.5 w-3.5"
                                            :class="sortIconClass('status')"
                                        />
                                    </button>

                                    <button
                                        type="button"
                                        class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('created_at')"
                                    >
                                        Created
                                        <component
                                            :is="sortIcon('created_at')"
                                            class="h-3.5 w-3.5"
                                            :class="sortIconClass('created_at')"
                                        />
                                    </button>

                                    <div class="col-span-1 flex h-10 items-center justify-end px-0 pr-3 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">
                                        Actions
                                    </div>
                                </div>
                            </div>

                            <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                <div
                                    v-for="(company, rowIndex) in props.companies.data"
                                    :key="company.id"
                                    :class="[
                                        'grid grid-cols-7 items-center border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light',
                                        rowIndex === props.companies.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                    ]"
                                >
                                    <div class="col-span-1 flex min-w-0 justify-start py-1.5 pl-3 font-semibold capitalize">
                                        <span class="truncate">{{ company.company_name }}</span>
                                    </div>

                                    <div class="col-span-1 flex justify-start py-1.5">
                                        <span class="rounded bg-custom-bg px-2 py-0.5 font-mono text-xs font-semibold text-custom-shadow dark:bg-custom-bg-light">
                                            {{ company.company_code }}
                                        </span>
                                    </div>

                                    <div class="col-span-1 flex min-w-0 items-center gap-1.5 py-1.5 text-sm text-custom-shadow/80">
                                        <RiMailLine class="h-3.5 w-3.5 shrink-0" />
                                        <span class="truncate">
                                            {{ company.company_email || '—' }}
                                        </span>
                                    </div>

                                    <div class="col-span-1 flex min-w-0 items-center gap-1.5 py-1.5 text-sm text-custom-shadow/80">
                                        <RiPhoneLine class="h-3.5 w-3.5 shrink-0" />
                                        <span class="truncate">
                                            {{ company.company_phone || '—' }}
                                        </span>
                                    </div>

                                    <div class="col-span-1 flex justify-start py-1.5">
                                        <Badge :class="['gap-1.5', statusClass(company.status ?? null)]">
                                            <span :class="['h-1.5 w-1.5 rounded-full', statusDot(company.status ?? null)]" />
                                            {{ humanizeStatus(company.status ?? null) }}
                                        </Badge>
                                    </div>

                                    <div class="col-span-1 flex justify-start py-1.5 text-sm text-custom-shadow/80">
                                        {{ company.created_at_human ?? '—' }}
                                    </div>

                                    <div class="col-span-1 flex justify-end py-1.5 pr-3 text-right">
                                        <DropdownMenu v-if="canViewCompany">
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="table-more"
                                                    size="icon-more"
                                                >
                                                    <RiMore2Line class="h-4 w-4" />
                                                    <span class="sr-only">Open actions</span>
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end" class="w-fit rounded-md shadow-lg">
                                                <DropdownMenuLabel class="text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">
                                                    {{ company.company_name }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    as-child
                                                    class="cursor-pointer rounded-md"
                                                >
                                                    <Link
                                                        :href="show({ company: company.id }).url"
                                                        class="flex items-center"
                                                    >
                                                        <RiFileSearchLine class="h-4 w-4" />
                                                        Review Company
                                                    </Link>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                            <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                                <img
                                    :src="emptyRafikiUrl"
                                    alt=""
                                    class="w-1/3 object-contain opacity-90"
                                    aria-hidden="true"
                                />
                                <div class="space-y-1">
                                    <p class="text-custom-shadow text-base font-semibold">No companies found</p>
                                    <p class="text-custom-shadow/80 text-sm">
                                        {{ hasActiveFilters ? 'Try adjusting or clearing your filters.' : 'Try adjusting your search.' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </Card>

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
