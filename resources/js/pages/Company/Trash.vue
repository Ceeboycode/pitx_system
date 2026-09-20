<script setup lang="ts">
import ForceDeleteCompanyDialog from '@/components/internal/company/ForceDeleteCompanyDialog.vue';
import RestoreCompanyDialog from '@/components/internal/company/RestoreCompanyDialog.vue';
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenuItem,
    DropdownMenuLabel,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, show, trash } from '@/routes/companies';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { RiArrowLeftLine, RiFilter2Line, RiRestartLine } from 'vue-remix-icons';
import { computed, ref, watch } from 'vue';
import { MainPanel, PanelLayout, SidePanel } from '@/components/ui/_panels';
import { CompanyPreviewCard } from '@/components/internal/preview-cards';
import { Table, TableCard, TableColumn, TableContent, TableData, TableHeader, TableMoreButton, TableRow } from '@/components/ui/_table';

type Company = {
    id: number;
    company_name: string;
    company_code: string;
    business_type?: string | null;
    deleted_at_human?: string | null;
    deleter?: { id: number; name: string } | null;
};

interface PaginatedCompanies {
    data: Company[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

const props = withDefaults(
    defineProps<{
        companies: PaginatedCompanies;
        filters?: {
            search: string | null;
            business_type: string | null;
            archived_by: string | null;
        };
    }>(),
    { filters: () => ({ search: null, business_type: null, archived_by: null }) },
);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
    { title: 'Archived Companies', href: trash().url },
];

const filterBusinessType = ref(props.filters.business_type ?? '');
const filterArchivedBy = ref(props.filters.archived_by ?? '');
const filterOpen = ref(false);

const activeFilterCount = computed(() => {
    let count = 0;
    if (filterBusinessType.value) count++;
    if (filterArchivedBy.value) count++;
    return count;
});

function applyFilters() {
    router.get(
        trash().url,
        {
            search: props.filters.search || undefined,
            business_type: filterBusinessType.value || undefined,
            archived_by: filterArchivedBy.value || undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['companies', 'filters'],
        },
    );
    filterOpen.value = false;
}

function clearFilters() {
    filterBusinessType.value = '';
    filterArchivedBy.value = '';
    router.get(
        trash().url,
        { search: props.filters.search || undefined },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['companies', 'filters'],
        },
    );
    filterOpen.value = false;
}

const restoreOpen = ref(false);
const forceDeleteOpen = ref(false);
const previewedCompany = ref<Company | null>(null);
const openMenuId = ref<number | null>(null);

// Drop the preview once its row leaves the list (e.g. after restoring it).
watch(() => props.companies.data, (rows) => {
    if (previewedCompany.value && !rows.some((row) => row.id === previewedCompany.value?.id)) {
        previewedCompany.value = null;
    }
});

const selectedCompany = ref<Company | null>(null);

function openRestore(company: Company) {
    selectedCompany.value = company;
    restoreOpen.value = true;
}
</script>

<template>
    <Head title="Archived Companies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
            <MainPanel>
                <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                    <CardHeader class="flex flex-row items-start gap-3">
                        <Button as-child variant="header-actions" size="icon">
                            <Link :href="index().url" aria-label="Back to companies">
                                <RiArrowLeftLine class="h-4 w-4" />
                            </Link>
                        </Button>
                        <div class="flex min-w-0 flex-col">
                            <CardTitle class="font-semibold">Archived Companies</CardTitle>
                            <CardDescription>Restore archived companies to the companies list.</CardDescription>
                        </div>
                    </CardHeader>

                    <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                        <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                            <div class="w-full">
                                <SearchInput
                                    :route="`${trash().url}?business_type=${encodeURIComponent(filterBusinessType)}&archived_by=${encodeURIComponent(filterArchivedBy)}`"
                                    :initial-value="props.filters.search"
                                    placeholder="Search archived companies..."
                                    :only="['companies', 'filters', 'flash']"
                                    :debounce="350"
                                />
                            </div>

                            <Popover v-model:open="filterOpen">
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="header-actions"
                                        size="icon-text"
                                        class="rounded-full"
                                        :class="activeFilterCount > 0 ? 'bg-custom-secondary/20 transition-all duration-200 hover:bg-custom-secondary/80 hover:text-custom-bg-light' : ''"
                                    >
                                        <RiFilter2Line class="h-3.5 w-3.5" />
                                        <span class="hidden lg:flex">
                                            {{ activeFilterCount > 0
                                                ? (activeFilterCount === 1 ? '1 filter active' : `${activeFilterCount} filters active`)
                                                : 'Filter' }}
                                        </span>
                                    </Button>
                                </PopoverTrigger>

                                <PopoverContent align="end">
                                    <div class="grid gap-y-2">
                                        <div class="flex flex-col gap-y-1">
                                            <p class="text-sm text-custom-shadow/80">Business Type</p>
                                            <Input v-model="filterBusinessType" placeholder="e.g. Bus operator" class="bg-custom-bg" />
                                        </div>
                                        <div class="flex flex-col gap-y-1">
                                            <p class="text-sm text-custom-shadow/80">Archived By</p>
                                            <Input v-model="filterArchivedBy" placeholder="Enter a user name" class="bg-custom-bg" />
                                        </div>

                                        <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light" />

                                        <div class="flex w-full flex-row items-center justify-between">
                                            <Button v-if="activeFilterCount > 0" size="sm" variant="destructive" @click="clearFilters">
                                                Clear
                                            </Button>
                                            <div class="ml-auto flex items-center gap-2">
                                                <Button variant="ghost-outline" size="sm" @click="filterOpen = false">Cancel</Button>
                                                <Button size="sm" variant="float-primary" @click="applyFilters">Apply</Button>
                                            </div>
                                        </div>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        </div>

                        <TableCard :table-data-length="props.companies.data.length">
                            <Table v-if="props.companies.data.length > 0">
                                <TableHeader>
                                    <TableColumn>Name and Code</TableColumn>
                                    <TableColumn>Business Type</TableColumn>
                                    <TableColumn>Archived At</TableColumn>
                                    <TableColumn>Archived By</TableColumn>
                                </TableHeader>

                                <TableContent>
                                    <TableRow
                                        v-for="(company, rowIndex) in props.companies.data"
                                        :key="company.id"
                                        :class="[
                                            rowIndex === props.companies.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                            previewedCompany?.id === company.id ? 'bg-custom-secondary/10' : '',
                                        ]"
                                        @click.left="previewedCompany = company"
                                        @dblclick="router.visit(show({ company: company.id }).url)"
                                    >
                                        <TableData>
                                            <div class="flex min-w-0 flex-col">
                                                <span class="truncate font-semibold capitalize">{{ company.company_name }}</span>
                                                <span class="truncate font-mono text-xs text-custom-shadow/70">{{ company.company_code }}</span>
                                            </div>
                                        </TableData>
                                        <TableData class="capitalize"><span class="truncate">{{ company.business_type || '—' }}</span></TableData>
                                        <TableData>{{ company.deleted_at_human ?? '—' }}</TableData>
                                        <TableData class="capitalize"><span class="truncate">{{ company.deleter?.name ?? '—' }}</span></TableData>

                                        <TableMoreButton
                                            :open="openMenuId === company.id"
                                            @update:open="(value) => (openMenuId = value ? company.id : null)"
                                        >
                                            <DropdownMenuLabel>{{ company.company_name }}</DropdownMenuLabel>
                                            <DropdownMenuItem class="group cursor-pointer" @click="openRestore(company)">
                                                <RiRestartLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                Restore
                                            </DropdownMenuItem>
                                        </TableMoreButton>
                                    </TableRow>
                                </TableContent>
                            </Table>

                            <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                                <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                                    <img :src="emptyRafikiUrl" alt="" class="w-1/3 object-contain opacity-90" aria-hidden="true" />
                                    <div class="space-y-1">
                                        <p class="text-base font-semibold text-custom-shadow">No archived companies found</p>
                                        <p class="text-sm text-custom-shadow/80">
                                            {{ props.filters.search || activeFilterCount > 0 ? 'Try adjusting your search or filters.' : 'Nothing has been archived yet.' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </TableCard>

                        <InertiaPagination
                            :links="props.companies.links"
                            :meta="{ from: props.companies.from, to: props.companies.to, total: props.companies.total }"
                        />
                    </CardContent>
                </Card>
            </MainPanel>
            
            <SidePanel v-if="previewedCompany" class="hidden lg:flex">
                <CompanyPreviewCard :company="previewedCompany" archived @close="previewedCompany = null" />
            </SidePanel>
        </PanelLayout>
            <RestoreCompanyDialog v-if="selectedCompany" v-model:open="restoreOpen" :company="selectedCompany" />
            <ForceDeleteCompanyDialog v-if="selectedCompany" v-model:open="forceDeleteOpen" :company="selectedCompany" />
    </AppLayout>
</template>
