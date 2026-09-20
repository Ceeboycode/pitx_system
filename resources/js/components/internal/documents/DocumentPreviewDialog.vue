<script setup lang="ts">
import { ref, watch } from 'vue';

import { AppDialog } from '@/components/ui/_app-dialog';
import { Badge } from '@/components/ui/_badge';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { RiDownloadLine, RiEyeLine, RiFileTextLine, RiMore2Line } from 'vue-remix-icons';

import { isImage, isPdf } from '@/lib/files';
import { formatDate, formatDateTime } from '@/lib/format';
import type { PreviewAction, PreviewDocument } from '@/lib/document-preview';

/**
 * Viewer for an uploaded document (company or vehicle). Callers describe the document with
 * companyDocumentPreview() / vehicleDocumentPreview() and the actions they allow with
 * companyPreviewActions() / vehiclePreviewActions() from '@/lib/document-preview'.
 */
const open = defineModel<boolean>('open');

const props = defineProps<{
    doc: PreviewDocument | null;
    actions?: PreviewAction[];
}>();

const emit = defineEmits<{
    action: [key: PreviewAction['key']];
}>();

const pdfLoadError = ref(false);

watch(open, (isOpen) => {
    if (isOpen) pdfLoadError.value = false;
});

function file(doc: PreviewDocument) {
    return { file_path: doc.fileName, mime_type: doc.mime, original_name: doc.fileName };
}

function runAction(key: PreviewAction['key']) {
    emit('action', key);
    open.value = false;
}
</script>

<template>
    <AppDialog v-model:open="open" :title="props.doc?.title ?? ''" size="viewer">
        <template #description>
            <span class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-muted-foreground">{{ props.doc?.typeLabel }}</span>
                <Badge v-if="props.doc?.status" :class="['gap-1.5', props.doc.status.class]">
                    <span :class="['h-1.5 w-1.5 rounded-full', props.doc.status.dot]" />
                    {{ props.doc.status.label }}
                </Badge>
            </span>
        </template>

        <div v-if="props.doc" class="relative max-h-[70vh] overflow-auto">
            <div v-if="isImage(file(props.doc))" class="flex min-h-[50vh] items-center justify-center">
                <img
                    :src="props.doc.url"
                    :alt="props.doc.title"
                    class="max-h-[70vh] max-w-full rounded-md object-contain"
                    @error="(e) => ((e.target as HTMLImageElement).src = '')"
                />
            </div>

            <div v-else-if="isPdf(file(props.doc))" class="h-[70vh] w-full">
                <iframe v-if="!pdfLoadError" :src="props.doc.url" class="h-full w-full border-0" @error="pdfLoadError = true" />
                <div v-else class="flex h-full flex-col items-center justify-center gap-3">
                    <RiFileTextLine class="h-12 w-12 shrink-0 opacity-30" />
                    <p class="text-sm">Your browser cannot preview this PDF inline.</p>
                    <Button as-child variant="float">
                        <a :href="props.doc.url" target="_blank" rel="noopener noreferrer">
                            <RiEyeLine class="h-4 w-4 shrink-0" />Open in new tab
                        </a>
                    </Button>
                </div>
            </div>

            <div v-else class="flex h-[50vh] items-center justify-center text-sm text-muted-foreground">
                Preview not available.
            </div>
        </div>

        <template #footer>
            <p v-if="props.doc" class="mr-auto text-xs text-muted-foreground">
                Issued: {{ formatDate(props.doc.issuedAt) }}
                <span class="mx-1">·</span>
                Expires: {{ formatDate(props.doc.expiresAt) }}
                <span class="mx-1">·</span>
                Uploaded: {{ formatDateTime(props.doc.uploadedAt) }}
            </p>

            <Popover v-if="props.doc && ((props.actions?.length ?? 0) > 0 || props.doc.downloadUrl)">
                <PopoverTrigger as-child>
                    <Button variant="float" size="icon" aria-label="More actions">
                        <RiMore2Line class="h-4 w-4 shrink-0" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent align="end" class="flex w-fit flex-col gap-1 p-2">
                    <Button
                        v-for="action in props.actions"
                        :key="action.key"
                        variant="float"
                        class="justify-start"
                        @click="runAction(action.key)"
                    >
                        <component :is="action.icon" class="h-4 w-4 shrink-0" />
                        {{ action.label }}
                    </Button>
                    <Button v-if="props.doc.downloadUrl" as-child variant="float" class="justify-start">
                        <a :href="props.doc.downloadUrl" target="_blank" rel="noopener noreferrer">
                            <RiDownloadLine class="h-4 w-4 shrink-0" />
                            Download
                        </a>
                    </Button>
                </PopoverContent>
            </Popover>

            <Button variant="float" @click="open = false">Close</Button>
        </template>
    </AppDialog>
</template>
