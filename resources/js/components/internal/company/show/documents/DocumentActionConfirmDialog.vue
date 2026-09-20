<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

import { ConfirmDialog } from '@/components/ui/_app-dialog';

import {
    destroy as destroyDoc,
    download as downloadCompanyDocument,
    unverify,
    verify,
} from '@/routes/companies/documents';

import { humanize } from '@/lib/format';
import type { CompanyDocument } from '@/types/company';

export type ConfirmAction = 'verify' | 'unverify' | 'delete' | 'download';

const open = defineModel<boolean>('open');

const props = defineProps<{
    doc: CompanyDocument | null;
    action: ConfirmAction;
    companyId: number;
}>();

const emit = defineEmits<{ done: [] }>();

const actionForm = useForm({});

function confirmTitle() {
    switch (props.action) {
        case 'verify':
            return 'Verify document?';
        case 'unverify':
            return 'Unverify document?';
        case 'delete':
            return 'Delete document?';
        case 'download':
            return 'Download document?';
    }
}

function confirmDocName() {
    const doc = props.doc;
    if (!doc) return '';
    return doc.original_name ?? humanize(doc.doc_type);
}

function confirmDescriptionPrefix() {
    switch (props.action) {
        case 'verify':
            return 'This will mark';
        case 'unverify':
            return 'This will set';
        case 'delete':
            return 'This will permanently remove';
        case 'download':
            return 'This will open';
    }
}

function confirmDescriptionSuffix() {
    switch (props.action) {
        case 'verify':
            return 'as verified.';
        case 'unverify':
            return 'back to pending.';
        case 'delete':
            return 'and delete the file.';
        case 'download':
            return 'in a new tab.';
    }
}

function runConfirmedAction() {
    const doc = props.doc;
    if (!doc || actionForm.processing) return;
    const urls = {
        verify: verify({ company: props.companyId, document: doc.id }).url,
        unverify: unverify({ company: props.companyId, document: doc.id }).url,
        delete: destroyDoc({ company: props.companyId, document: doc.id }).url,
        download: downloadCompanyDocument({
            company: props.companyId,
            document: doc.id,
        }).url,
    } as const;
    if (props.action === 'download') {
        window.open(urls.download, '_blank', 'noopener,noreferrer');
        open.value = false;
        emit('done');
        return;
    }
    if (props.action === 'delete') {
        actionForm.delete(urls.delete, {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
                emit('done');
            },
        });
        return;
    }
    actionForm.patch(urls[props.action], {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            emit('done');
        },
    });
}
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        :title="confirmTitle()"
        :tone="props.action === 'delete' || props.action === 'unverify' ? 'negative' : 'primary'"
        confirm-label="Confirm"
        processing-label="Processing..."
        :processing="actionForm.processing"
        @confirm="runConfirmedAction"
    >
        <template #description>
            {{ confirmDescriptionPrefix() }}
            <span class="font-semibold text-custom-accent-3">{{ confirmDocName() }}</span>
            {{ confirmDescriptionSuffix() }}
        </template>
    </ConfirmDialog>
</template>
