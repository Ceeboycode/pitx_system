<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import DocumentsTable from '@/components/internal/documents/DocumentsTable.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

import { docStatusClass, docStatusDot, docStatusLabel } from '@/lib/company-documents';
import type { PreviewDocument } from '@/lib/document-preview';

import { documentLabel, type CompanyProfile } from './types';

const props = defineProps<{
    company: CompanyProfile;
}>();

/** Which document the page shows in its side panel (the page owns the panel). */
const previewedId = defineModel<number | null>('previewedId', { default: null });

const rows = computed<PreviewDocument[]>(() =>
    props.company.documents.map((doc) => ({
        id: doc.id,
        title: documentLabel(doc.doc_type),
        typeLabel: documentLabel(doc.doc_type),
        url: '',
        status: { label: docStatusLabel(doc), class: docStatusClass(doc), dot: docStatusDot(doc) },
        expiresAt: doc.expires_at,
        uploadedAt: doc.updated_at,
        remarks: doc.remarks,
    })),
);

const canResubmit = computed(
    () => props.company.status === 'needs_revision' && props.company.documents.some((doc) => ['invalid', 'expired'].includes(doc.status)),
);
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-start justify-between gap-4">
            <div class="flex flex-col">
                <CardTitle>Documents</CardTitle>
                <CardDescription>Required documents can be re-uploaded only when your company needs revision.</CardDescription>
            </div>
            <Button v-if="canResubmit" as-child variant="float-primary">
                <Link href="/registration/status">Resubmit Documents</Link>
            </Button>
        </CardHeader>

        <CardContent class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
            <DocumentsTable
                :documents="rows"
                :previewed-id="previewedId"
                empty-description="Documents submitted by your company will appear here."
                @preview="previewedId = previewedId === $event ? null : $event"
            />
        </CardContent>
    </Card>
</template>
