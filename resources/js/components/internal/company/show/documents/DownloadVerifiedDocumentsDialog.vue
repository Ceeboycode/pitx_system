<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';

import { downloadBulk } from '@/routes/companies/documents';

const open = defineModel<boolean>('open');

const props = defineProps<{
    companyId: number;
    verifiedCount: number;
}>();

function downloadVerifiedZip() {
    const { url } = downloadBulk({ company: props.companyId });
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.style.display = 'none';
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value =
        (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)
            ?.content ?? '';
    form.appendChild(csrf);
    document.body.appendChild(form);
    form.submit();
    setTimeout(() => {
        try {
            document.body.removeChild(form);
        } catch {}
    }, 1000);
}

function runBulkDownload() {
    open.value = false;
    downloadVerifiedZip();
}
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        title="Download Verified Documents"
        confirm-label="Confirm"
        :confirm-disabled="verifiedCount === 0"
        @confirm="runBulkDownload"
    >
        <template #description>
            This will download a ZIP containing only verified documents for this company.
            <span v-if="verifiedCount > 0">({{ verifiedCount }} verified)</span>
            <span v-else> No verified documents found.</span>
        </template>
    </ConfirmDialog>
</template>
