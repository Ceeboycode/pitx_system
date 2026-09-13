<script setup lang="ts">
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
    <AlertDialog v-model:open="open">
        <AlertDialogContent class="rounded-lg p-4">
            <AlertDialogHeader>
                <AlertDialogTitle
                    >Download verified documents?</AlertDialogTitle
                >
                <AlertDialogDescription>
                    This will download a ZIP containing only verified
                    documents for this company.
                    <span v-if="verifiedCount > 0"
                        >({{ verifiedCount }} verified)</span
                    >
                    <span v-else> No verified documents found.</span>
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel
                    variant="outline"
                    class="rounded-lg"
                    @click="open = false"
                    >Cancel</AlertDialogCancel
                >
                <AlertDialogAction
                    variant="outline"
                    class="rounded-lg border-0 bg-primary text-white cursor-pointer hover:bg-slate-100"
                    :disabled="verifiedCount === 0"
                    @click="runBulkDownload"
                >
                    Continue
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
