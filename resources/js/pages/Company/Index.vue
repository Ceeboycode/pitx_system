<script setup lang="ts">
import ArchiveCompanyDialog from '@/components/company/ArchiveCompanyDialog.vue';
import CreateCompanyDialog from '@/components/company/CreateCompanyDialog.vue';
import EditCompanyDialog from '@/components/company/EditCompanyDialog.vue';
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
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

import AppLayout from '@/layouts/AppLayout.vue';
import { index, show, trash } from '@/routes/companies';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Archive, Edit, Eye, Plus, Trash } from 'lucide-vue-next';
import { ref } from 'vue';

type Company = {
    id: number;
    company_name: string;
    created_at_human?: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
];

const props = defineProps<{
    companies: any;
    filters: { search: string | null };
}>();

const createOpen = ref(false);

const editOpen = ref(false);
const archiveOpen = ref(false);

const selectedCompany = ref<Company | null>(null);

function openEdit(company: Company) {
    selectedCompany.value = company;
    editOpen.value = true;
}

function openArchive(company: Company) {
    selectedCompany.value = company;
    archiveOpen.value = true;
}
</script>

<template>
    <Head title="Companies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card class="mx-10 mt-4">
                <CardHeader>
                    <CardTitle>Companies</CardTitle>
                    <CardDescription
                        >Manage your companies here.</CardDescription
                    >

                    <CardAction>
                        <Button
                            as-child
                            size="sm"
                            variant="outline"
                            class="mr-2"
                        >
                            <Link :href="trash().url">
                                <Trash class="mr-2 h-4 w-4" />
                                View Trash
                            </Link>
                        </Button>

                        <Button size="sm" @click="createOpen = true">
                            <Plus class="mr-2 h-4 w-4" />
                            New Company
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="max-w-sm">
                        <SearchInput
                            :route="index().url"
                            :initial-value="filters.search"
                            placeholder="Search companies..."
                            :only="['companies', 'filters']"
                            :debounce="350"
                        />
                    </div>

                    <Table>
                        <TableCaption>List of companies.</TableCaption>

                        <TableHeader>
                            <TableRow>
                                <TableHead>Company Name</TableHead>
                                <TableHead>Created At</TableHead>
                                <TableHead>Actions</TableHead>
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
                                <TableCell >
                                    {{ company.created_at_human }}
                                </TableCell>

                                <TableCell class="space-x-2">
                                    <Button
                                        as-child
                                        size="sm"
                                        variant="outline"
                                    >
                                        <Link
                                            :href="
                                                show({ company: company.id })
                                                    .url
                                            "
                                        >
                                            <Eye class="mr-2 h-4 w-4" />
                                            View
                                        </Link>
                                    </Button>

                                    <Button
                                        size="sm"
                                        variant="default"
                                        @click="openEdit(company)"
                                    >
                                        <Edit class="mr-2 h-4 w-4" />
                                        Edit
                                    </Button>

                                    <Button
                                        size="sm"
                                        variant="archive"
                                        @click="openArchive(company)"
                                    >
                                        <Archive class="mr-2 h-4 w-4" />
                                        Archive
                                    </Button>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="companies.data.length === 0">
                                <TableCell
                                    colspan="3"
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
