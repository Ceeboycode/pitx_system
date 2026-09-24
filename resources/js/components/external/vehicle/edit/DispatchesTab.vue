<script setup lang="ts">
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import { Table, TableCard, TableColumn, TableContent, TableData, TableHeader, TableRow } from '@/components/ui/_table';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

import { humanize } from '@/lib/format';

import type { DispatchRow } from './types';

defineProps<{
    dispatches: DispatchRow[];
}>();

function statusVariant(status?: string | null) {
    if (status === 'departed') return 'success';
    if (status === 'arrived') return 'blue';
    if (status === 'pending') return 'warning';

    return 'muted';
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Dispatches</CardTitle>
            <CardDescription>The most recent dispatches of this vehicle.</CardDescription>
        </CardHeader>

        <CardContent class="flex min-h-0 flex-1 flex-col">
            <TableCard :table-data-length="dispatches.length">
                <Table v-if="dispatches.length > 0">
                    <TableHeader hide-actions-column>
                        <TableColumn>Gate</TableColumn>
                        <TableColumn>Bay</TableColumn>
                        <TableColumn>Pax</TableColumn>
                        <TableColumn>Status</TableColumn>
                        <TableColumn>Arrived</TableColumn>
                        <TableColumn>Departed</TableColumn>
                    </TableHeader>

                    <TableContent>
                        <TableRow
                            v-for="(dispatch, rowIndex) in dispatches"
                            :key="dispatch.id"
                            :class="rowIndex === dispatches.length - 1 ? 'rounded-b-md border-b-0' : ''"
                        >
                            <TableData class="pl-3 font-semibold">{{ dispatch.gate_name || '—' }}</TableData>
                            <TableData>{{ dispatch.bay_number ?? '—' }}</TableData>
                            <TableData><span class="tabular-nums">{{ dispatch.pax_count ?? '—' }}</span></TableData>
                            <TableData>
                                <Badge :variant="statusVariant(dispatch.status)">{{ humanize(dispatch.status) }}</Badge>
                            </TableData>
                            <TableData>{{ dispatch.arrived_at || '—' }}</TableData>
                            <TableData>{{ dispatch.departed_at || '—' }}</TableData>
                        </TableRow>
                    </TableContent>
                </Table>

                <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                    <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                        <img :src="emptyRafikiUrl" alt="" class="w-1/3 object-contain opacity-90" aria-hidden="true" />
                        <div class="space-y-1">
                            <p class="text-base font-semibold text-custom-shadow">No dispatches found</p>
                            <p class="text-sm text-custom-shadow/80">Dispatches of this vehicle will appear here.</p>
                        </div>
                    </div>
                </div>
            </TableCard>
        </CardContent>
    </Card>
</template>
