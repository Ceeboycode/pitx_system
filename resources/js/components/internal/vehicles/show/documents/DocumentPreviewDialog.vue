<script setup lang="ts">
import { ref, watch } from 'vue';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import Button from '@/components/ui/button/Button.vue';
import { Badge } from '@/components/ui/_badge';

import { download as downloadCompanyDocument } from '@/routes/companies/documents';

import {
    RiCheckLine,
    RiCloseLine,
    RiDownloadLine,
    RiEyeLine,
    RiFileTextLine,
    RiMore2Line,
} from 'vue-remix-icons';

import { formatDate, formatDateTime, humanize } from '@/lib/format';
import { fileUrl, isImage, isPdf } from '@/lib/files';
import {
    docStatusClass as statusClass,
    docStatusDot as statusDot,
} from '@/lib/company-documents';
import DocumentStatusBadge from '@/components/internal/company/show/documents/DocumentStatusBadge.vue';
import type { CompanyDocument } from '@/types/company';

const open = defineModel<boolean>('open');

const props = defineProps<{
    doc: CompanyDocument | null;
    companyId: number;
}>();

const emit = defineEmits<{
    verify: [doc: CompanyDocument];
    reject: [docId: number];
}>();

const pdfLoadError = ref(false);

watch(open, (isOpen) => {
    if (isOpen) pdfLoadError.value = false;
});

function closePreview() {
    open.value = false;
}

function onVerify() {
    if (!props.doc) return;
    emit('verify', props.doc);
    closePreview();
}

function onReject() {
    if (!props.doc) return;
    emit('reject', props.doc.id);
    closePreview();
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="flex max-h-[90vh] w-full flex-col gap-0 rounded-lg py-4 px-6"
            className="[&>button:last-child]:hidden"
        >
            <DialogHeader
                class="shrink-0"
            >
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0 space-y-1">
                        <DialogTitle class="truncate text-base">
                            {{
                                doc?.original_name ??
                                humanize(doc?.doc_type)
                            }}
                        </DialogTitle>
                        <DialogDescription
                            class="flex flex-wrap items-center gap-2"
                        >
                            <span class="text-xs text-muted-foreground">{{
                                humanize(doc?.doc_type)
                            }}</span>
                            <DocumentStatusBadge :doc="doc" />
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="relative flex-1 overflow-auto py-4">
                <div
                    v-if="doc && isImage(doc)"
                    class="flex min-h-[50vh] items-center justify-center"
                >
                    <img
                        :src="fileUrl(doc)"
                        :alt="
                            doc.original_name ?? doc.doc_type
                        "
                        class="max-h-[70vh] max-w-full rounded-lg object-contain"
                        @error="
                            (e) => ((e.target as HTMLImageElement).src = '')
                        "
                    />
                </div>
                <div
                    v-else-if="doc && isPdf(doc)"
                    class="h-[70vh] w-full"
                >
                    <iframe
                        v-if="!pdfLoadError"
                        :src="fileUrl(doc)"
                        class="h-full w-full border-0"
                        @error="pdfLoadError = true"
                    />
                    <div
                        v-else
                        class="flex h-full flex-col items-center justify-center"
                    >
                        <RiFileTextLine class="h-12 w-12 shrink-0 opacity-30" />
                        <p class="text-sm">
                            Your browser cannot preview this PDF inline.
                        </p>
                        <Button
                            as-child
                            variant="outline"
                            class="rounded-lg"
                        >
                            <a
                                :href="fileUrl(doc)"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <RiEyeLine class="mr-2 h-4 w-4 shrink-0" />Open in new tab
                            </a>
                        </Button>
                    </div>
                </div>
            </div>

            <DialogFooter
                v-if="doc"
                class="shrink-0 flex flex-row items-center"
            >
                <p class="flex-1 text-xs text-muted-foreground">
                    Issued:
                    {{ formatDate(doc?.issued_at ?? null) }}<br>
                    Expires:
                    {{ formatDate(doc?.expires_at ?? null) }}
                </p>
                <p class="flex-1 text-xs text-muted-foreground">
                    Uploaded:
                    {{ formatDateTime(doc?.created_at ?? null) }}
                </p>
                <div class="flex flex-1 flex-row gap-x-2 justify-end">
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button
                                variant="outline"
                                class="rounded-lg cursor-pointer hover:bg-slate-100"
                            >
                                <RiMore2Line class="h-4 w-4 shrink-0" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent
                            align="end"
                            class="w-fit rounded-lg border-slate-200 shadow-lg p-0 gap-2"
                        >
                            <div
                                v-if="doc.status !== 'verified'"
                                class="cursor-pointer flex items-center gap-2 rounded-lg px-4 py-2 text-sm hover:bg-slate-100"
                                @click="onVerify"
                            >
                                <RiCheckLine class="h-4 w-4 shrink-0" />
                                Verify
                            </div>
                            <div
                                v-if="doc.status !== 'invalid'"
                                class="cursor-pointer flex items-center gap-2 rounded-lg px-4 py-2 text-sm hover:bg-slate-100"
                                @click="onReject"
                            >
                                <RiCloseLine class="h-4 w-4 shrink-0" />
                                Mark as Invalid
                            </div>
                            <a
                                :href="
                                    downloadCompanyDocument({
                                        company: companyId,
                                        document: doc.id,
                                    }).url
                                "
                                target="_blank"
                                rel="noopener noreferrer"
                                class="cursor-pointer flex items-center gap-2 rounded-lg px-4 py-2 text-sm hover:bg-slate-100"
                            >
                                <RiDownloadLine class="h-4 w-4 shrink-0" />
                                Download
                            </a>
                        </PopoverContent>
                    </Popover>
                    <Button
                        variant="outline"
                        class="rounded-lg cursor-pointer hover:bg-slate-100"
                        @click="closePreview"
                        >Close</Button
                    >
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
