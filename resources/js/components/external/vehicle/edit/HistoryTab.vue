<script setup lang="ts">
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { RiAddLine, RiDeleteBin7Line, RiEditLine } from 'vue-remix-icons';

import { formatDateTime, humanize } from '@/lib/format';

import type { HistoryEntry } from './types';

defineProps<{
    history: HistoryEntry[];
}>();

const actionIcons = {
    created: RiAddLine,
    updated: RiEditLine,
    deleted: RiDeleteBin7Line,
} as const;

function iconFor(action: string) {
    return actionIcons[action as keyof typeof actionIcons] ?? RiEditLine;
}

function verbFor(action: string) {
    if (action === 'created') return 'added';
    if (action === 'deleted') return 'removed';

    return 'updated';
}

function subjectFor(entry: HistoryEntry) {
    return entry.subject === 'document' ? (entry.document_label ?? 'a document') : 'the vehicle';
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>History</CardTitle>
            <CardDescription>Changes made to this vehicle and its documents.</CardDescription>
        </CardHeader>

        <CardContent>
            <ol v-if="history.length > 0" class="space-y-1">
                <li
                    v-for="entry in history"
                    :key="entry.id"
                    class="flex items-start gap-3 rounded-md p-2 transition-colors hover:bg-custom-secondary/10"
                >
                    <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-custom-bg text-custom-shadow dark:bg-custom-bg-light">
                        <component :is="iconFor(entry.action)" class="h-4 w-4 shrink-0" />
                    </span>

                    <div class="min-w-0 flex-1 text-sm text-custom-shadow">
                        <p>
                            <span class="font-semibold">{{ entry.actor }}</span>
                            {{ verbFor(entry.action) }}
                            <span class="font-semibold">{{ subjectFor(entry) }}</span>
                        </p>
                        <p class="text-xs text-custom-shadow/70">{{ formatDateTime(entry.created_at) }}</p>

                        <ul v-if="entry.changes.length" class="mt-1 space-y-0.5 text-xs text-custom-shadow/80">
                            <li v-for="change in entry.changes" :key="change.field" class="break-words">
                                <span class="font-semibold">{{ humanize(change.field) }}:</span>
                                {{ change.old ?? '—' }}
                                <span aria-hidden="true">→</span>
                                {{ change.new ?? '—' }}
                            </li>
                        </ul>
                    </div>
                </li>
            </ol>

            <div v-else class="flex items-center justify-center p-6 text-center">
                <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                    <img :src="emptyRafikiUrl" alt="" class="w-1/3 object-contain opacity-90" aria-hidden="true" />
                    <div class="space-y-1">
                        <p class="text-base font-semibold text-custom-shadow">No history yet</p>
                        <p class="text-sm text-custom-shadow/80">Changes to this vehicle will appear here.</p>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
