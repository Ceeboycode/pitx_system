<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableRow, TableCell } from '@/components/ui/table';
import { ArrowLeft } from 'lucide-vue-next';

import { index, show } from '@/routes/vehicle-types';

const { vehicleType } = defineProps<{
    vehicleType: {
        id: number;
        type_name: string;
        is_active: boolean;
        created_at_human: string | null;
        updated_at_human: string | null;
        creator: { id: number; name: string } | null;
        updater: { id: number; name: string } | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vehicle Types Table', href: index().url },
    { title: 'Vehicle Type Details', href: show(vehicleType.id).url },
];
</script>

<template>
    <Head :title="`Vehicle Type - ${vehicleType.type_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        
        <div class="w-full px-4 py-6 capitalize sm:px-6">
            
            <div class="mx-auto w-full max-w-4xl">
                <Card>
                    <CardHeader>
                        
                        <CardTitle class="text-2xl">
                            {{ vehicleType.type_name }}
                        </CardTitle>

                        <CardDescription>
                            Details for {{ vehicleType.type_name }}
                        </CardDescription>

                        
                        <CardAction>
                            <Button as-child variant="link" size="sm">
                                <Link :href="index().url" class="cursor-pointer">
                                    <ArrowLeft class="mr-2 h-4 w-4" />
                                    Back to Vehicle Types
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
                                        Status
                                    </TableCell>
                                    <TableCell class="py-3">
                                        {{ vehicleType.is_active ? 'Active' : 'Inactive' }}
                                    </TableCell>
                                </TableRow>

                                <TableRow>
                                    <TableCell
                                        class="w-56 py-3 text-sm font-medium text-muted-foreground"
                                    >
                                        Created At
                                    </TableCell>
                                    <TableCell class="py-3">
                                        {{ vehicleType.created_at_human ?? '—' }}
                                    </TableCell>
                                </TableRow>

                                <TableRow>
                                    <TableCell
                                        class="w-56 py-3 text-sm font-medium text-muted-foreground"
                                    >
                                        Created By
                                    </TableCell>
                                    <TableCell class="py-3">
                                        {{ vehicleType.creator?.name ?? 'N/A' }}
                                    </TableCell>
                                </TableRow>

                                <TableRow>
                                    <TableCell
                                        class="w-56 py-3 text-sm font-medium text-muted-foreground"
                                    >
                                        Updated At
                                    </TableCell>
                                    <TableCell class="py-3">
                                        {{ vehicleType.updated_at_human ?? '—' }}
                                    </TableCell>
                                </TableRow>

                                <TableRow>
                                    <TableCell
                                        class="w-56 py-3 text-sm font-medium text-muted-foreground"
                                    >
                                        Updated By
                                    </TableCell>
                                    <TableCell class="py-3">
                                        {{ vehicleType.updater?.name ?? 'N/A' }}
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
