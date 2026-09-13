<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

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

function confirmDescription() {
    const doc = props.doc;
    if (!doc) return '';
    const name = doc.original_name ?? humanize(doc.doc_type);
    switch (props.action) {
        case 'verify':
            return `This will mark "${name}" as verified.`;
        case 'unverify':
            return `This will set "${name}" back to pending.`;
        case 'delete':
            return `This will permanently remove "${name}" and delete the file.`;
        case 'download':
            return `This will open "${name}" in a new tab.`;
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
    <AlertDialog v-model:open="open">
        <AlertDialogContent class="rounded-lg p-4">
            <AlertDialogHeader>
                <AlertDialogTitle>{{ confirmTitle() }}</AlertDialogTitle>
                <AlertDialogDescription>{{
                    confirmDescription()
                }}</AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel
                    variant="outline"
                    class="rounded-lg cursor-pointer hover:bg-slate-100"
                    :disabled="actionForm.processing"
                    @click="open = false"
                    >Cancel</AlertDialogCancel
                >
                <AlertDialogAction
                    variant="outline"
                    class="rounded-lg border-0 bg-primary text-white cursor-pointer"
                    :disabled="actionForm.processing"
                    @click="runConfirmedAction"
                >
                    {{ actionForm.processing ? 'Processing...' : 'Continue' }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
