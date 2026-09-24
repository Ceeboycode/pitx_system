<script setup lang="ts">
import { InputMessage } from '@/components/ui/_input-message';
import { Badge } from '@/components/ui/_badge';
import { PreviewCard, PreviewCardRow } from '@/components/ui/_preview-card';

import type { PreviewDocument } from '@/lib/document-preview';
import { formatDate, formatDateTime, isExpired } from '@/lib/format';

const props = defineProps<{
    doc: PreviewDocument;
}>();

defineEmits<{ close: [] }>();
</script>

<template>
    <PreviewCard :title="props.doc.typeLabel" description="Document preview">
        <div class="space-y-3 pt-2">
            <PreviewCardRow label="Status">
                <span class="flex flex-wrap items-center justify-end gap-1.5">
                    <Badge v-if="props.doc.status" :class="['gap-1.5', props.doc.status.class]">
                        <span :class="['h-1.5 w-1.5 rounded-full', props.doc.status.dot]" />
                        {{ props.doc.status.label }}
                    </Badge>
                    <Badge
                        v-if="props.doc.expiredBadge"
                        class="gap-1.5 border-rose-200 bg-rose-100 text-rose-600 dark:border-rose-800 dark:bg-rose-950 dark:text-rose-400"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500" />
                        Expired
                    </Badge>
                </span>
            </PreviewCardRow>

            <PreviewCardRow label="File">
                <span class="break-all">{{ props.doc.fileName || '—' }}</span>
            </PreviewCardRow>
            <PreviewCardRow label="Issued">{{ formatDate(props.doc.issuedAt) }}</PreviewCardRow>
            <PreviewCardRow label="Expires">
                <span :class="isExpired(props.doc.expiresAt) ? 'font-semibold text-rose-600 dark:text-rose-400' : ''">{{ formatDate(props.doc.expiresAt) }}</span>
            </PreviewCardRow>
            <PreviewCardRow label="Uploaded">
                <span v-if="props.doc.uploadedBy" class="block">{{ props.doc.uploadedBy }}</span>
                <span class="block" :class="props.doc.uploadedBy ? 'text-xs text-custom-shadow/70' : ''">{{ formatDateTime(props.doc.uploadedAt) }}</span>
            </PreviewCardRow>
            <PreviewCardRow v-if="props.doc.verifiedBy || props.doc.verifiedAt" label="Verified">
                <span v-if="props.doc.verifiedBy" class="block">{{ props.doc.verifiedBy }}</span>
                <span class="block text-xs text-custom-shadow/70">{{ formatDateTime(props.doc.verifiedAt) }}</span>
            </PreviewCardRow>

            <div v-if="props.doc.remarks" class="space-y-1">
                <p class="text-sm font-semibold text-custom-shadow">Remarks</p>
                <InputMessage class="mt-0 whitespace-pre-wrap" :message="props.doc.remarks ?? undefined" />
            </div>

            <p class="pt-1 text-xs text-custom-shadow/60">Double-click the row to open the document.</p>
        </div>
    </PreviewCard>
</template>
