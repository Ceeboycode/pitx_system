<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Table, TableBody, TableCell, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, show } from '@/routes/companies';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    company: {
        id: number;
        company_name: string;
        created_at?: string;
        updated_at_human?: string;
        creator?: { name: string } | null;
        updater?: { name: string } | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
    {
        title: 'Company Details',
        href: show({ company: props.company.id }).url,
    },
];

function formatDate(date?: string) {
    if (!date) return '—';

    return new Date(date).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <Head :title="company.company_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Page wrapper -->
        <div class="w-full px-4 py-6 capitalize sm:px-6">
            <!-- Centered container -->
            <div class="mx-auto w-full max-w-4xl">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-2xl">
                            {{ company.company_name }}
                        </CardTitle>

                        <CardDescription>
                            Details for {{ company.company_name }}
                        </CardDescription>

                        <CardAction>
                            <Button as-child variant="link" size="sm">
                                <Link :href="index().url">
                                    Back to Companies
                                </Link>
                            </Button>
                        </CardAction>
                    </CardHeader>

                    <CardContent>
                        <Table class="w-full">
                            <TableBody>
                                <TableRow>
                                    <TableCell
                                        class="w-56 py-3 text-sm font-medium text-muted-foreground"
                                    >
                                        Created At
                                    </TableCell>
                                    <TableCell class="py-3">
                                        {{ formatDate(company.created_at) }}
                                    </TableCell>
                                </TableRow>

                                <TableRow>
                                    <TableCell
                                        class="w-56 py-3 text-sm font-medium text-muted-foreground"
                                    >
                                        Created By
                                    </TableCell>
                                    <TableCell class="py-3">
                                        {{ company.creator?.name ?? 'N/A' }}
                                    </TableCell>
                                </TableRow>

                                <TableRow>
                                    <TableCell
                                        class="w-56 py-3 text-sm font-medium text-muted-foreground"
                                    >
                                        Updated At
                                    </TableCell>
                                    <TableCell class="py-3">
                                        {{ company.updated_at_human ?? '—' }}
                                    </TableCell>
                                </TableRow>

                                <TableRow>
                                    <TableCell
                                        class="w-56 py-3 text-sm font-medium text-muted-foreground"
                                    >
                                        Updated By
                                    </TableCell>
                                    <TableCell class="py-3">
                                        {{ company.updater?.name ?? 'N/A' }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
