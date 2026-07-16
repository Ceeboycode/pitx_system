<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';

import { Button } from '@/components/ui/button';
import Label from '@/components/ui/label/Label.vue';
import Separator from '@/components/ui/separator/Separator.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

import {
    AlertCircle,
    CheckCircle2,
    XCircle,
} from 'lucide-vue-next';

import {
    RiArchive2Line,
    RiCloseLine,
    RiFileUploadLine,
    RiLoader2Line,
} from 'vue-remix-icons';

const open = defineModel<boolean>('open');
const emit = defineEmits<{ done: [] }>();

type Phase = 'idle' | 'uploading' | 'done' | 'error';

const phase       = ref<Phase>('idle');
const progress    = ref(0);          
const file        = ref<File | null>(null);
const fileInputEl = ref<HTMLInputElement | null>(null);
const errorMsg    = ref('');

type Summary = {
    imported: string[];
    skipped: string[];
    errors: string[];
};

const summary = ref<Summary | null>(null);

const hasFile      = computed(() => file.value !== null);
const fileSizeMB   = computed(() => file.value ? (file.value.size / 1024 / 1024).toFixed(2) : '0');
const isProcessing = computed(() => phase.value === 'uploading');

const summaryHasResults = computed(() =>
    summary.value &&
    (summary.value.imported.length + summary.value.skipped.length + summary.value.errors.length) > 0
);

function onFileChange(e: Event) {
    const el = e.target as HTMLInputElement;
    const selected = el.files?.[0] ?? null;

    if (selected && !selected.name.toLowerCase().endsWith('.zip')) {
        errorMsg.value = 'Please select a valid ZIP backup file.';
        file.value = null;
        return;
    }

    errorMsg.value = '';
    file.value = selected;
    phase.value = 'idle';
    summary.value = null;
}

function clearFile() {
    file.value = null;
    errorMsg.value = '';
    phase.value = 'idle';
    summary.value = null;
    if (fileInputEl.value) fileInputEl.value.value = '';
}

async function submit() {
    if (!file.value) return;

    phase.value   = 'uploading';
    progress.value = 0;
    errorMsg.value = '';
    summary.value  = null;

    const formData = new FormData();
    formData.append('backup', file.value);
    formData.append('_method', 'POST');

    try {
        const res = await axios.post('/companies/import', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress(e) {
                progress.value = e.total ? Math.round((e.loaded / e.total) * 100) : 50;
            },
        });

        phase.value   = 'done';
        progress.value = 100;
        summary.value  = res.data.summary;
        emit('done');

    } catch (err: any) {
        phase.value    = 'error';
        const data     = err.response?.data;
        errorMsg.value = data?.message
            ?? data?.errors?.backup?.[0]
            ?? 'Import failed. Please check the file and try again.';
    }
}

function handleClose(val: boolean) {
    if (!val) {
        
        setTimeout(() => {
            phase.value    = 'idle';
            progress.value = 0;
            file.value     = null;
            errorMsg.value = '';
            summary.value  = null;
            if (fileInputEl.value) fileInputEl.value.value = '';
        }, 200);
    }
    open.value = val;
}
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Import Company Backup</DialogTitle>
                <DialogDescription>
                    Upload a ZIP backup exported from this system. Existing company codes will be skipped.
                </DialogDescription>
            </DialogHeader>

            <form class="flex flex-col gap-y-2 px-6" @submit.prevent="submit">
                <div class="space-y-1">
                    <!-- CODE: <Label for="backup_file">Company Backup</Label> -->
                    <label
                        v-if="!hasFile"
                        for="backup_file"
                        class="flex cursor-pointer items-center gap-3 rounded-md border border-dashed border-custom-bg-dark p-3 transition-colors hover:bg-custom-bg dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
                    >
                        <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-md border border-dashed border-custom-bg-dark dark:border-custom-bg-light">
                            <RiFileUploadLine class="h-7 w-7 text-custom-shadow/80" />
                        </div>
                        <div class="space-y-1">
                            <!-- CODE: <p class="text-sm font-semibold text-custom-shadow">Select a backup file</p> -->
                            <p class="text-sm text-custom-shadow/80">
                                <span class="font-semibold">File format: </span>.zip<br />
                                <span class="font-semibold">Max. file size: </span>100 MB
                            </p>
                        </div>
                        <input id="backup_file" ref="fileInputEl" type="file" accept=".zip" class="sr-only" @change="onFileChange" />
                    </label>

                    <div
                        v-else
                        class="flex items-center gap-3 rounded-md border border-dashed border-custom-bg-dark p-3 dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
                    >
                        <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-md bg-custom-bg dark:bg-custom-bg-light">
                            <RiArchive2Line class="h-7 w-7 text-custom-shadow/80" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-custom-shadow">{{ file!.name }}</p>
                            <p class="text-sm text-custom-shadow/80">{{ fileSizeMB }} MB</p>
                        </div>
                        <Button
                            v-if="phase === 'idle'"
                            type="button"
                            aria-label="Remove backup file"
                            class="flex h-7 w-7 shrink-0 cursor-pointer items-center rounded-full border border-custom-shadow/50 text-custom-shadow transition-all duration-300 hover:border-destructive hover:bg-destructive/20 hover:text-destructive"
                            @click="clearFile"
                        >
                            <RiCloseLine class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <div v-if="phase === 'uploading'" class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs text-custom-shadow/80">
                        <span>Uploading and processing...</span>
                        <span>{{ progress }}%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-custom-bg dark:bg-custom-bg-light">
                        <div class="h-full bg-primary transition-all duration-300" :style="{ width: `${progress}%` }" />
                    </div>
                </div>

                <div v-if="errorMsg" class="flex items-start gap-2 rounded-md border border-destructive/30 bg-destructive/5 px-3 py-2.5 text-sm text-destructive">
                    <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                    {{ errorMsg }}
                </div>

                <div v-if="phase === 'done' && summary" class="max-h-56 space-y-2 overflow-y-auto">
                    <div v-if="summary.imported.length" class="rounded-md border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-800 dark:bg-emerald-950/30">
                        <div class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                            <CheckCircle2 class="h-3.5 w-3.5" />
                            {{ summary.imported.length }} imported
                        </div>
                        <ul class="space-y-0.5"><li v-for="item in summary.imported" :key="item" class="text-xs text-emerald-700 dark:text-emerald-400">{{ item }}</li></ul>
                    </div>

                    <div v-if="summary.skipped.length" class="rounded-md border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-950/30">
                        <div class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-amber-700 dark:text-amber-400">
                            <AlertCircle class="h-3.5 w-3.5" />
                            {{ summary.skipped.length }} skipped (already exist)
                        </div>
                        <ul class="space-y-0.5"><li v-for="item in summary.skipped" :key="item" class="text-xs text-amber-700 dark:text-amber-400">{{ item }}</li></ul>
                    </div>

                    <div v-if="summary.errors.length" class="rounded-md border border-destructive/30 bg-destructive/5 p-3">
                        <div class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-destructive">
                            <XCircle class="h-3.5 w-3.5" />
                            {{ summary.errors.length }} failed
                        </div>
                        <ul class="space-y-0.5"><li v-for="item in summary.errors" :key="item" class="text-xs text-destructive">{{ item }}</li></ul>
                    </div>

                    <div v-if="!summaryHasResults" class="rounded-md bg-custom-bg px-3 py-2.5 text-center text-sm text-custom-shadow/80 dark:bg-custom-bg-dark">
                        No companies were found in the backup file.
                    </div>
                </div>

                <Separator />
                <DialogFooter class="gap-2">
                    <Button type="button" variant="ghost-outline" :disabled="isProcessing" @click="handleClose(false)">
                        {{ phase === 'done' ? 'Close' : 'Cancel' }}
                    </Button>
                    <Button v-if="phase !== 'done'" type="submit" variant="float-primary" :disabled="!hasFile || isProcessing">
                        <RiLoader2Line v-if="isProcessing" class="h-4 w-4 animate-spin" />
                        <RiFileUploadLine v-else class="h-4 w-4" />
                        {{ isProcessing ? 'Importing...' : 'Import' }}
                    </Button>
                    <Button v-else type="button" variant="float-primary" @click="clearFile(); phase = 'idle'">
                        Import Another
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
