<script setup lang="ts">
import { ref, watch } from 'vue';
import { CheckCircle2, XCircle } from 'lucide-vue-next';
import {
    RiDownloadLine,
    RiEyeLine,
    RiFileTextLine,
    RiMore2Line,
    RiRestartLine,
} from 'vue-remix-icons';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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

type VehicleDocument = {
    id: number;
    document_type: string;
    file_name?: string | null;
    file_mime_type?: string | null;
    file_url?: string | null;
    status?: string | null;
    issued_at?: string | null;
    expires_at?: string | null;
    remarks?: string | null;
};

const open = defineModel<boolean>('open');

const props = defineProps<{
    doc: VehicleDocument | null;
    canVerify?: boolean;
    canUnverify?: boolean;
    canInvalidate?: boolean;
}>();

const emit = defineEmits<{
    (e: 'verify', doc: VehicleDocument): void;
    (e: 'unverify', doc: VehicleDocument): void;
    (e: 'invalidate', doc: VehicleDocument): void;
}>();

const pdfLoadError = ref(false);

watch(open, (isOpen) => {
    if (isOpen) pdfLoadError.value = false;
});

function humanize(text?: string | null) {
    if (!text) return '—';
    return text.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatDate(date?: string | null) {
    if (!date) return '—';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function statusClass(status?: string | null): string {
    switch (status) {
        case 'verified':
        case 'active':
        case 'complete':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'pending':
        case 'draft':
        case 'for_verification':
        case 'partial':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'invalid':
        case 'expired':
        case 'needs_revision':
        case 'none':
            return 'bg-rose-100 text-rose-600 border-rose-200';
        default:
            return 'bg-slate-100 text-slate-500 border-0';
    }
}

function statusDot(status?: string | null): string {
    switch (status) {
        case 'verified':
        case 'active':
        case 'complete':
            return 'bg-emerald-500';
        case 'pending':
        case 'draft':
        case 'for_verification':
        case 'partial':
            return 'bg-amber-500';
        case 'invalid':
        case 'expired':
        case 'needs_revision':
        case 'none':
            return 'bg-rose-500';
        default:
            return 'bg-slate-400';
    }
}

function fileUrl(doc: VehicleDocument) {
    return doc.file_url ?? '';
}

function isImage(doc: VehicleDocument) {
    if (doc.file_mime_type) return doc.file_mime_type.startsWith('image/');
    return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(
        (doc.file_name ?? '').split('.').pop()?.toLowerCase() ?? '',
    );
}

function isPdf(doc: VehicleDocument) {
    if (doc.file_mime_type) return doc.file_mime_type === 'application/pdf';
    return (doc.file_name ?? '').split('.').pop()?.toLowerCase() === 'pdf';
}

function emitAndClose(
    event: 'verify' | 'unverify' | 'invalidate',
) {
    if (!props.doc) return;
    emit(event, props.doc);
    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="flex max-h-[90vh] w-full flex-col gap-0 rounded-lg py-4 px-6"
            className="[&>button:last-child]:hidden"
        >
            <DialogHeader class="shrink-0">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0 space-y-1">
                        <DialogTitle class="truncate text-base">
                            {{
                                doc?.file_name ??
                                humanize(doc?.document_type)
                            }}
                        </DialogTitle>
                        <DialogDescription
                            class="flex flex-wrap items-center gap-2"
                        >
                            <span class="text-xs text-muted-foreground">{{
                                humanize(doc?.document_type)
                            }}</span>
                            <Badge
                                :class="['gap-1.5', statusClass(doc?.status)]"
                            >
                                <span
                                    :class="[
                                        'h-1.5 w-1.5 rounded-full',
                                        statusDot(doc?.status),
                                    ]"
                                />
                                {{ humanize(doc?.status) }}
                            </Badge>
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
                        :alt="doc.file_name ?? doc.document_type"
                        class="max-h-[70vh] max-w-full rounded-lg object-contain"
                        @error="(e) => ((e.target as HTMLImageElement).src = '')"
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
                        <Button as-child variant="outline" class="rounded-lg">
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

                <div
                    v-else
                    class="flex h-[50vh] items-center justify-center text-sm text-muted-foreground"
                >
                    Preview not available.
                </div>
            </div>

            <DialogFooter
                v-if="doc"
                class="shrink-0 flex flex-row items-center"
            >
                <p class="flex-1 text-xs text-muted-foreground">
                    Issued: {{ formatDate(doc?.issued_at ?? null) }}<br>
                    Expires: {{ formatDate(doc?.expires_at ?? null) }}
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
                                v-if="canVerify && doc.status !== 'verified'"
                                class="cursor-pointer flex items-center gap-2 rounded-lg px-4 py-2 text-sm hover:bg-slate-100"
                                @click="emitAndClose('verify')"
                            >
                                <CheckCircle2 class="h-4 w-4 shrink-0" />
                                Verify
                            </div>
                            <div
                                v-if="canUnverify && doc.status === 'verified'"
                                class="cursor-pointer flex items-center gap-2 rounded-lg px-4 py-2 text-sm hover:bg-slate-100"
                                @click="emitAndClose('unverify')"
                            >
                                <RiRestartLine class="h-4 w-4 shrink-0" />
                                Move to Pending
                            </div>
                            <div
                                v-if="canInvalidate"
                                class="cursor-pointer flex items-center gap-2 rounded-lg px-4 py-2 text-sm hover:bg-slate-100"
                                @click="emitAndClose('invalidate')"
                            >
                                <XCircle class="h-4 w-4 shrink-0" />
                                Mark Invalid
                            </div>
                            <a
                                v-if="fileUrl(doc)"
                                :href="fileUrl(doc)"
                                target="_blank"
                                rel="noopener noreferrer"
                                download
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
                        @click="open = false"
                    >Close</Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
