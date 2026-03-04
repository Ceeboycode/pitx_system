<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { create, index } from '@/routes/dispatches';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

import InertiaPagination from '@/components/InertiaPagination.vue';
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
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type DispatchRow = {
    id: number;
    plate_number: string;
    pax_count: number;
    bay_number: string | null;
    status: string;
    arrived_at_formatted: string | null;
    departed_at_formatted: string | null;
    vehicle?: {
        id: number;
        plate_number: string;
        route?: { route_name: string };
        vehicle_type?: { type_name: string };
    };
    dispatcher?: {
        id: number;
        name: string;
    };
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const props = defineProps<{
    dispatches: {
        data: DispatchRow[];
        links: PaginationLink[];
    };
    company: {
        id: number;
        name: string | null;
        code: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dispatches', href: index().url },
];

function badgeVariant(status: string) {
    if (status === 'pending') return 'secondary';
    if (status === 'arrived') return 'default';
    if (status === 'departed') return 'outline';
    if (status === 'settled') return 'default';
    if (status === 'cancelled') return 'destructive';
    return 'secondary';
}
</script>

<template>
    <Head title="Dispatches" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card>
                <CardHeader>
                    <div>
                        <CardTitle
                            >Dispatches -
                            {{ props.company.code ?? '—' }}</CardTitle
                        >
                        <CardDescription>{{
                            props.company.name ?? '—'
                        }}</CardDescription>
                    </div>

                    <CardAction v-if="props.company?.id">
                        <Button size="sm" as-child>
                            <Link
                                :href="
                                    create({ company: props.company.id }).url
                                "
                            >
                                Add Dispatch
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent>
                    <Table>
                        <TableCaption
                            >Dispatch history and movement
                            records.</TableCaption
                        >

                        <TableHeader>
                            <TableRow>
                                <TableHead>Plate</TableHead>
                                <TableHead>Route</TableHead>
                                <TableHead>Vehicle Type</TableHead>
                                <TableHead>Dispatcher</TableHead>
                                <TableHead class="text-right">Pax</TableHead>
                                <TableHead>Bay</TableHead>
                                <TableHead>Arrival (IN)</TableHead>
                                <TableHead>Departure (OUT)</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Action</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-if="props.dispatches.data.length === 0">
                                <TableCell
                                    colspan="9"
                                    class="py-8 text-center text-muted-foreground"
                                >
                                    No dispatches found.
                                </TableCell>
                            </TableRow>

                            <TableRow
                                v-for="dispatch in props.dispatches.data"
                                :key="dispatch.id"
                            >
                                <TableCell class="font-medium">{{
                                    dispatch.plate_number
                                }}</TableCell>

                                <TableCell>
                                    {{
                                        dispatch.vehicle?.route?.route_name ??
                                        '—'
                                    }}
                                </TableCell>

                                <TableCell>
                                    {{
                                        dispatch.vehicle?.vehicle_type
                                            ?.type_name ?? '—'
                                    }}
                                </TableCell>

                                <TableCell>
                                    {{ dispatch.dispatcher?.name ?? '—' }}
                                </TableCell>

                                <TableCell class="text-right">
                                    {{ dispatch.pax_count }}
                                </TableCell>

                                <TableCell>{{
                                    dispatch.bay_number ?? '—'
                                }}</TableCell>
                                <TableCell>{{
                                    dispatch.arrived_at_formatted ?? '—'
                                }}</TableCell>
                                <TableCell>{{
                                    dispatch.departed_at_formatted ?? '—'
                                }}</TableCell>

                                <TableCell class="capitalize">
                                    <Badge
                                        :variant="badgeVariant(dispatch.status)"
                                    >
                                        {{ dispatch.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Button variant="outline" size="sm">
                                        Departure
                                    </Button>
                                    <Button size="sm">
                                        Edit
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <InertiaPagination :links="props.dispatches.links" />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
