<script setup lang="ts">
import ForceDeleteCompanyDialog from '@/components/company/ForceDeleteCompanyDialog.vue';
import RestoreCompanyDialog from '@/components/company/RestoreCompanyDialog.vue';
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, trash } from '@/routes/companies';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { RiArrowLeftSLine, RiFilter2Line, RiMore2Line, RiRestartLine } from 'vue-remix-icons';
import { computed, ref } from 'vue';

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
const selectedCompany = ref<Company | null>(null);

function openRestore(company: Company) {
    selectedCompany.value = company;
    restoreOpen.value = true;
}
</script>

<template>
    <Head title="Archived Companies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4">
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row items-start gap-3">
                    <Button as-child variant="header-actions" size="icon">
                        <Link :href="index().url" aria-label="Back to companies">
                            <RiArrowLeftSLine class="h-4 w-4" />
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
                                    :class="activeFilterCount > 0 ? 'bg-custom-secondary/20 transition-all duration-300 hover:bg-custom-secondary/80 hover:text-custom-bg-light' : ''"
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

                    <Card
                        :class="[
                            'flex min-h-0 max-h-fit flex-1 flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none',
                            props.companies.data.length === 0 ? 'border-dashed' : 'border-solid',
                        ]"
                    >
                        <div v-if="props.companies.data.length > 0" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                            <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                                <div class="grid grid-cols-[1.5fr_1fr_1fr_1fr_5rem] gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                    <div class="flex h-10 items-center justify-start pl-3 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Name and Code</div>
                                    <div class="flex h-10 items-center justify-start text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Business Type</div>
                                    <div class="flex h-10 items-center justify-start text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Archived At</div>
                                    <div class="flex h-10 items-center justify-start text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Archived By</div>
                                    <div class="flex h-10 items-center justify-end pr-3 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Actions</div>
                                </div>
                            </div>

                            <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                <div
                                    v-for="(company, rowIndex) in props.companies.data"
                                    :key="company.id"
                                    :class="[
                                        'grid grid-cols-[1.5fr_1fr_1fr_1fr_5rem] items-center gap-2 border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light',
                                        rowIndex === props.companies.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                    ]"
                                >
                                    <div class="flex min-w-0 flex-col justify-start py-1.5 pl-3">
                                        <span class="truncate font-semibold capitalize">{{ company.company_name }}</span>
                                        <span class="truncate font-mono text-xs text-custom-shadow/70">{{ company.company_code }}</span>
                                    </div>
                                    <div class="flex min-w-0 justify-start py-1.5 text-sm capitalize">
                                        <span class="truncate">{{ company.business_type || '—' }}</span>
                                    </div>
                                    <div class="flex justify-start py-1.5 text-sm">{{ company.deleted_at_human ?? '—' }}</div>
                                    <div class="flex min-w-0 justify-start py-1.5 text-sm capitalize">
                                        <span class="truncate">{{ company.deleter?.name ?? '—' }}</span>
                                    </div>
                                    <div class="flex justify-end py-1.5 pr-3 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="table-more" size="icon-more">
                                                    <RiMore2Line class="h-4 w-4" />
                                                    
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel>{{ company.company_name }}</DropdownMenuLabel>
                                                <DropdownMenuItem class="group" @click="openRestore(company)">
                                                    <RiRestartLine class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                    Restore
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                            </div>
                        </div>

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
                    </Card>

                    <InertiaPagination
                        :links="props.companies.links"
                        :meta="{ from: props.companies.from, to: props.companies.to, total: props.companies.total }"
                    />
                </CardContent>
            </Card>

            <RestoreCompanyDialog v-if="selectedCompany" v-model:open="restoreOpen" :company="selectedCompany" />
            <ForceDeleteCompanyDialog v-if="selectedCompany" v-model:open="forceDeleteOpen" :company="selectedCompany" />
        </div>
    </AppLayout>
</template>
