<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';

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
    <Dialog v-model:open="open">
        <DialogContent class="max-w-md px-6" :show-close-button="false">
            <DialogHeader class="px-0">
                <DialogTitle>Download verified documents?</DialogTitle>
                <DialogDescription>
                    This will download a ZIP containing only verified
                    documents for this company.
                    <span v-if="verifiedCount > 0"
                        >({{ verifiedCount }} verified)</span
                    >
                    <span v-else> No verified documents found.</span>
                </DialogDescription>
            </DialogHeader>
            <Separator />
            <DialogFooter class="pt-3 gap-2 sm:justify-end">
                <Button variant="ghost-outline" @click="open = false">
                    Cancel
                </Button>
                <Button
                    variant="float-primary"
                    :disabled="verifiedCount === 0"
                    @click="runBulkDownload"
                >
                    Confirm
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
