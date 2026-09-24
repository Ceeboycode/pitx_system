<script setup lang="ts">
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

import { docStatusClass, docStatusDot } from '@/lib/company-documents';
import { formatDateTime, humanize } from '@/lib/format';

import type { CompanyProfileChangeRequest } from './types';

defineProps<{
    requests: CompanyProfileChangeRequest[];
}>();

const hiddenKeys = ['logo_path', 'logo_url', '_supporting_documents'];

function changesFor(request: CompanyProfileChangeRequest) {
    return Object.entries(request.requested_values ?? {})
        .filter(([key]) => !hiddenKeys.includes(key))
        .map(([key, value]) => ({
            field: key,
            value: value === null || value === undefined || value === '' ? '—' : String(value),
        }));
}

function statusKey(status: string) {
    return status === 'approved' ? 'verified' : status;
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>History</CardTitle>
            <CardDescription>Profile changes you have submitted for admin review.</CardDescription>
        </CardHeader>

        <CardContent>
            <ol v-if="requests.length > 0" class="space-y-1">
                <li
                    v-for="request in requests"
                    :key="request.id"
                    class="rounded-md p-2 text-sm text-custom-shadow transition-colors duration-200 hover:bg-custom-secondary/10"
                >
                    <div class="flex flex-row items-center justify-between gap-2">
                        <p class="font-semibold">Change request #{{ request.id }}</p>
                        <Badge :class="['gap-1.5', docStatusClass(statusKey(request.status))]">
                            <span :class="['h-1.5 w-1.5 rounded-full', docStatusDot(statusKey(request.status))]" />
                            {{ humanize(request.status) }}
                        </Badge>
                    </div>
                    <p class="text-xs text-custom-shadow/70">{{ formatDateTime(request.created_at) }}</p>

                    <ul class="mt-1 space-y-0.5 text-xs text-custom-shadow/80">
                        <li v-for="change in changesFor(request)" :key="change.field" class="break-words">
                            <span class="font-semibold">{{ humanize(change.field) }}:</span>
                            {{ change.value }}
                        </li>
                    </ul>

                    <p v-if="request.status === 'rejected'" class="mt-1 text-xs text-custom-shadow/80">
                        Reason: {{ request.rejection_reason || 'No reason provided.' }}
                    </p>
                </li>
            </ol>

            <div v-else class="flex items-center justify-center p-6 text-center">
                <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                    <img :src="emptyRafikiUrl" alt="" class="w-1/3 object-contain opacity-90" aria-hidden="true" />
                    <div class="space-y-1">
                        <p class="text-base font-semibold text-custom-shadow">No history yet</p>
                        <p class="text-sm text-custom-shadow/80">Submitted profile changes will appear here.</p>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
