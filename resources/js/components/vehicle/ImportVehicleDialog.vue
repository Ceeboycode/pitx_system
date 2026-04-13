<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';

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
    AlertCircle,
    CheckCircle2,
    FileArchive,
    Loader2,
    UploadCloud,
    X,
    XCircle,
} from 'lucide-vue-next';

/* ── Model & emits ──────────────────────────────────────────────────────── */

const open = defineModel<boolean>('open');
const emit = defineEmits<{ done: [] }>();

/* ── State ──────────────────────────────────────────────────────────────── */

type Phase = 'idle' | 'uploading' | 'done' | 'error';

const phase       = ref<Phase>('idle');
const progress    = ref(0);          // 0–100
const file        = ref<File | null>(null);
const fileInputEl = ref<HTMLInputElement | null>(null);
const errorMsg    = ref('');

type Summary = {
    imported: string[];
    skipped: string[];
    errors: string[];
};

const summary = ref<Summary | null>(null);

/* ── Computed ───────────────────────────────────────────────────────────── */

const hasFile      = computed(() => file.value !== null);
const fileSizeMB   = computed(() => file.value ? (file.value.size / 1024 / 1024).toFixed(2) : '0');
const isProcessing = computed(() => phase.value === 'uploading');

const summaryHasResults = computed(() =>
    summary.value &&
    (summary.value.imported.length + summary.value.skipped.length + summary.value.errors.length) > 0
);

/* ── File selection ─────────────────────────────────────────────────────── */

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

/* ── Upload ─────────────────────────────────────────────────────────────── */

async function submit() {
    if (!file.value) return;

    phase.value    = 'uploading';
    progress.value = 0;
    errorMsg.value = '';
    summary.value  = null;

    const formData = new FormData();
    formData.append('backup', file.value);
    formData.append('_method', 'POST');

    try {
        const res = await axios.post('/vehicles/import', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress(e) {
                progress.value = e.total ? Math.round((e.loaded / e.total) * 100) : 50;
            },
        });

        phase.value    = 'done';
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

/* ── Close / reset ──────────────────────────────────────────────────────── */

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
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Import Vehicle Backup</DialogTitle>
                <DialogDescription>
                    Upload a <code class="rounded bg-muted px-1 text-xs">.zip</code> backup file exported from this system.
                    Existing vehicles (matched by plate number) will be skipped.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-1">

                <!-- ── File drop / select area ─────────────────────────── -->
                <div v-if="!hasFile">
                    <label
                        for="vehicle_backup_file"
                        class="flex cursor-pointer flex-col items-center gap-3 rounded-lg border-2 border-dashed border-border bg-muted/30 px-6 py-10 transition-colors hover:border-primary/50 hover:bg-muted/50"
                    >
                        <UploadCloud class="h-10 w-10 text-muted-foreground/50" />
                        <div class="text-center">
                            <p class="text-sm font-medium">Click to select a backup file</p>
                            <p class="text-xs text-muted-foreground">ZIP files only · Max 100 MB</p>
                        </div>
                        <input
                            id="vehicle_backup_file"
                            ref="fileInputEl"
                            type="file"
                            accept=".zip"
                            class="sr-only"
                            @change="onFileChange"
                        />
                    </label>
                </div>

                <!-- ── Selected file info ──────────────────────────────── -->
                <div
                    v-else
                    class="flex items-center gap-3 rounded-lg border bg-muted/30 px-4 py-3"
                >
                    <FileArchive class="h-8 w-8 shrink-0 text-primary" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ file!.name }}</p>
                        <p class="text-xs text-muted-foreground">{{ fileSizeMB }} MB</p>
                    </div>
                    <Button
                        v-if="phase === 'idle'"
                        variant="ghost"
                        size="icon"
                        class="h-7 w-7 shrink-0"
                        @click="clearFile"
                    >
                        <X class="h-4 w-4" />
                    </Button>
                </div>

                <!-- ── Progress bar ────────────────────────────────────── -->
                <div v-if="phase === 'uploading'" class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs text-muted-foreground">
                        <span>Uploading and processing…</span>
                        <span>{{ progress }}%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full bg-primary transition-all duration-300"
                            :style="{ width: `${progress}%` }"
                        />
                    </div>
                </div>

                <!-- ── Error ───────────────────────────────────────────── -->
                <div
                    v-if="errorMsg"
                    class="flex items-start gap-2 rounded-md border border-destructive/30 bg-destructive/5 px-3 py-2.5 text-sm text-destructive"
                >
                    <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                    {{ errorMsg }}
                </div>

                <!-- ── Result summary ──────────────────────────────────── -->
                <div v-if="phase === 'done' && summary" class="space-y-3">

                    <!-- Imported -->
                    <div v-if="summary.imported.length" class="rounded-md border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-800 dark:bg-emerald-950/30">
                        <div class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                            <CheckCircle2 class="h-3.5 w-3.5" />
                            {{ summary.imported.length }} imported
                        </div>
                        <ul class="space-y-0.5">
                            <li
                                v-for="item in summary.imported"
                                :key="item"
                                class="text-xs text-emerald-700 dark:text-emerald-400"
                            >
                                {{ item }}
                            </li>
                        </ul>
                    </div>

                    <!-- Skipped -->
                    <div v-if="summary.skipped.length" class="rounded-md border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-950/30">
                        <div class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-amber-700 dark:text-amber-400">
                            <AlertCircle class="h-3.5 w-3.5" />
                            {{ summary.skipped.length }} skipped (already exist)
                        </div>
                        <ul class="space-y-0.5">
                            <li
                                v-for="item in summary.skipped"
                                :key="item"
                                class="text-xs text-amber-700 dark:text-amber-400"
                            >
                                {{ item }}
                            </li>
                        </ul>
                    </div>

                    <!-- Errors -->
                    <div v-if="summary.errors.length" class="rounded-md border border-destructive/30 bg-destructive/5 p-3">
                        <div class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-destructive">
                            <XCircle class="h-3.5 w-3.5" />
                            {{ summary.errors.length }} failed
                        </div>
                        <ul class="space-y-0.5">
                            <li
                                v-for="item in summary.errors"
                                :key="item"
                                class="text-xs text-destructive"
                            >
                                {{ item }}
                            </li>
                        </ul>
                    </div>

                    <!-- Nothing happened -->
                    <div
                        v-if="!summaryHasResults"
                        class="rounded-md border bg-muted/40 px-3 py-2.5 text-center text-sm text-muted-foreground"
                    >
                        No vehicles were found in the backup file.
                    </div>
                </div>

            </div>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button variant="outline" :disabled="isProcessing" @click="handleClose(false)">
                    {{ phase === 'done' ? 'Close' : 'Cancel' }}
                </Button>
                <Button
                    v-if="phase !== 'done'"
                    :disabled="!hasFile || isProcessing"
                    @click="submit"
                >
                    <Loader2 v-if="isProcessing" class="mr-2 h-4 w-4 animate-spin" />
                    <UploadCloud v-else class="mr-2 h-4 w-4" />
                    {{ isProcessing ? 'Importing…' : 'Import Backup' }}
                </Button>
                <Button v-else variant="default" @click="clearFile(); phase = 'idle'">
                    Import Another
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
