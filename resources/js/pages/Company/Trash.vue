<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';

import ForceDeleteCompanyDialog from '@/components/company/ForceDeleteCompanyDialog.vue';
import RestoreCompanyDialog from '@/components/company/RestoreCompanyDialog.vue';

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

import AppLayout from '@/layouts/AppLayout.vue';
import { index, trash } from '@/routes/companies';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

import {
    Archive,
    ArrowLeft,
    Building2,
    MoreHorizontal,
    RotateCcw,
    Trash2,
} from 'lucide-vue-next';

import { ref } from 'vue';

type Company = {
    id: number;
    company_name: string;
    company_code: string;
    deleted_at_human?: string;
    deleter?: { id: number; name: string } | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
    { title: 'Archived', href: trash().url },
];

const props = defineProps<{
    companies: any;
    filters: { search: string | null };
}>();

const restoreOpen     = ref(false);
const forceDeleteOpen = ref(false);
const selectedCompany = ref<Company | null>(null);

function openRestore(company: Company) {
    selectedCompany.value = company;
    restoreOpen.value = true;
}

function openForceDelete(company: Company) {
    selectedCompany.value = company;
    forceDeleteOpen.value = true;
}
</script>

<template>
    <Head title="Archived Companies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <!-- <span>Change Requests</span> -->
                         <!-- TODO: make the text straight, not wrapped -->
                        <Button
                            as-child
                            variant="outline"
                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100 mr-2"
                        >
                            <Link :href="index().url">
                                <ArrowLeft class="h-4 w-4" />
                            </Link>
                        </Button>
                        Archives
                        <span class="ml-2 flex flex-1 items-center">
                            <hr class="h-px w-full border border-rose-500 " />
                            <div class="border-7 border-rose-500 rounded-xs">
                                <div class="border-3 border-white rounded-xs"></div>
                            </div>
                        </span>
                    </CardTitle>
                    <CardDescription class="mt-1">
                        Archived companies can be restored or permanently deleted.
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="trash().url"
                                :initial-value="filters.search"
                                placeholder="Search archived companies…"
                                :only="['companies', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>
                    </div>
                    <div class="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 hover:bg-muted/40">
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Company Name</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Code</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Archived At</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Archived By</TableHead>
                                    <TableHead class="text-right text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Actions</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <!-- Empty state -->
                                <TableRow v-if="companies.data.length === 0" class="hover:bg-transparent">
                                    <TableCell colspan="5" class="py-20 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                                                <Building2 class="h-6 w-6 text-muted-foreground/40" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-foreground">No archived companies</p>
                                                <p class="mt-0.5 text-xs text-muted-foreground">Nothing has been archived yet.</p>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="company in companies.data"
                                    :key="company.id"
                                    class="transition-colors hover:bg-muted/30"
                                >
                                    <!-- Company Name -->
                                    <TableCell>
                                        <p class="text-sm font-semibold capitalize">{{ company.company_name }}</p>
                                    </TableCell>

                                    <!-- Code -->
                                    <TableCell>
                                        <span class="rounded bg-muted px-2 py-0.5 font-mono text-xs font-semibold">
                                            {{ company.company_code }}
                                        </span>
                                    </TableCell>

                                    <!-- Archived At -->
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ company.deleted_at_human ?? '—' }}
                                    </TableCell>

                                    <!-- Archived By -->
                                    <TableCell class="text-sm capitalize text-muted-foreground">
                                        {{ company.deleter?.name ?? '—' }}
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="text-right">
                                        <DropdownMenu>
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

                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    class="rounded-lg text-emerald-700 focus:bg-emerald-50 focus:text-emerald-700"
                                                    @click="openRestore(company)"
                                                >
                                                    <RotateCcw class="mr-2 h-4 w-4" />
                                                    Restore Company
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

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

            <RestoreCompanyDialog
                v-if="selectedCompany"
                v-model:open="restoreOpen"
                :company="selectedCompany"
            />

            <ForceDeleteCompanyDialog
                v-if="selectedCompany"
                v-model:open="forceDeleteOpen"
                :company="selectedCompany"
            />
        </div>
    </AppLayout>
</template>