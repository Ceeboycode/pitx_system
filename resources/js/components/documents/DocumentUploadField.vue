<script lang="ts">
export type DocumentUploadRules = {
    extensions: string[];
    accept: string;
    maxMb: number;
    previewableExtensions: string[];
};

export const defaultDocumentUploadRules: DocumentUploadRules = {
    extensions: ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
    accept: '.pdf,.doc,.docx,.jpg,.jpeg,.png',
    maxMb: 5,
    previewableExtensions: ['pdf', 'jpg', 'jpeg', 'png'],
};
</script>

<script setup lang="ts">
import { computed, onUnmounted, ref, useId, watch } from 'vue';
import { CalendarDate } from '@internationalized/date';

import { Button } from '@/components/ui/button';
import { Calendar as CalendarPicker } from '@/components/ui/calendar';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { InputMessage } from '@/components/ui/_input-message';
import {
    RiArrowDownSLine,
    RiArrowRightSLine,
    RiCalendarLine,
    RiCloseLine,
    RiEyeLine,
} from 'vue-remix-icons';

/**
 * A single document's upload prompt: file picker with allowed-types/size hint, a selected-file
 * summary (name, size, Preview, Remove), optional Issue/Expiration date pickers, and a built-in
 * preview dialog for images and PDFs. Styled after the document prompts in CompanyRegistration.vue.
 */
const props = withDefaults(
    defineProps<{
        label: string;
        required?: boolean;
        /** Overrides the default "Allowed: X. Maximum: Y." hint under the label. */
        hint?: string;
        /** Small note shown under the file row, e.g. why reuploading is currently disabled. */
        note?: string;
        file: File | null;
        /** The name of a file already on record, shown when no new file has been chosen yet. */
        existingFileName?: string | null;
        issuedAt?: string;
        expiresAt?: string;
        showDates?: boolean;
        disabled?: boolean;
        accept?: string;
        uploadRules?: DocumentUploadRules;
        fileError?: string;
        issuedAtError?: string;
        expiresAtError?: string;
    }>(),
    {
        required: false,
        hint: undefined,
        note: undefined,
        existingFileName: null,
        issuedAt: '',
        expiresAt: '',
        showDates: true,
        disabled: false,
        accept: undefined,
        uploadRules: () => defaultDocumentUploadRules,
        fileError: undefined,
        issuedAtError: undefined,
        expiresAtError: undefined,
    },
);

const emit = defineEmits<{
    'update:file': [file: File | null];
    'update:issuedAt': [value: string];
    'update:expiresAt': [value: string];
}>();

const uid = useId();
const fileInputId = `document-file-${uid}`;
const resetKey = ref(0);
const isOpen = ref(true);

const allowedFileTypesText = computed(() =>
    props.uploadRules.extensions.map((extension) => extension.toUpperCase()).join(', '),
);
const defaultHint = computed(
    () => `Allowed: ${allowedFileTypesText.value}. Maximum: ${props.uploadRules.maxMb} MB.`,
);

function fileExtension(file?: File | null) {
    return file?.name.split('.').pop()?.toLowerCase() ?? '';
}

function canPreviewFile(file?: File | null) {
    return !!file && props.uploadRules.previewableExtensions.includes(fileExtension(file));
}

function previewType(file: File): 'image' | 'pdf' {
    return fileExtension(file) === 'pdf' ? 'pdf' : 'image';
}

function formatFileSize(bytes?: number | null) {
    if (!bytes || bytes <= 0) return '0 B';

    const units = ['B', 'KB', 'MB', 'GB'];
    let value = bytes;
    let unitIndex = 0;

    while (value >= 1024 && unitIndex < units.length - 1) {
        value /= 1024;
        unitIndex++;
    }

    return `${value.toFixed(value >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

const previewUrl = ref<string | null>(null);

watch(
    () => props.file,
    (file, previousFile) => {
        // A file cleared by the parent (a form reset) leaves the old name in the native input.
        if (!file && previousFile) resetKey.value += 1;

        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
            previewUrl.value = null;
        }

        if (file && canPreviewFile(file)) {
            previewUrl.value = URL.createObjectURL(file);
        }
    },
);

onUnmounted(() => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
});

const previewOpen = ref(false);

function openPreview() {
    if (!props.file || !previewUrl.value || !canPreviewFile(props.file)) return;
    previewOpen.value = true;
}

function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    emit('update:file', input.files?.[0] ?? null);
}

function clearFile() {
    emit('update:file', null);
    resetKey.value += 1;
}

function parseCalendarDate(value?: string): CalendarDate | undefined {
    if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) return undefined;

    const [year, month, day] = value.split('-').map(Number);

    try {
        return new CalendarDate(year, month, day);
    } catch {
        return undefined;
    }
}

function formatCalendarDate(value: CalendarDate | undefined): string {
    if (!value) return '';
    return `${value.year}-${String(value.month).padStart(2, '0')}-${String(value.day).padStart(2, '0')}`;
}

const issuedPickerOpen = ref(false);
const expiresPickerOpen = ref(false);

function selectIssuedAt(value: CalendarDate | undefined) {
    emit('update:issuedAt', formatCalendarDate(value));
    issuedPickerOpen.value = false;
}

function selectExpiresAt(value: CalendarDate | undefined) {
    emit('update:expiresAt', formatCalendarDate(value));
    expiresPickerOpen.value = false;
}
</script>

<template>
    <div class="space-y-2">
        <button
            type="button"
            class="flex w-full cursor-pointer items-center justify-between gap-3 rounded-md bg-custom-bg px-3 py-2 text-left transition-colors hover:bg-custom-secondary/10 dark:bg-custom-bg-light/60"
            :aria-expanded="isOpen"
            @click="isOpen = !isOpen"
        >
            <span class="text-sm font-semibold">
                {{ label }}
                <span v-if="required" class="text-destructive">*</span>
                <span v-else class="text-xs font-normal text-custom-shadow/80">(optional)</span>
            </span>

            <RiArrowDownSLine v-if="isOpen" class="h-4 w-4 shrink-0 text-custom-shadow" />
            <RiArrowRightSLine v-else class="h-4 w-4 shrink-0 text-custom-shadow" />
        </button>

        <div v-if="isOpen" class="flex gap-2 flex-col items-start gap-2 rounded-md border px-3 py-3 transition-colors">
            <div class="flex items-start gap-2 flex-col w-full">
                <!-- <Label :for="fileInputId">Document</Label> -->
                <div class="flex flex-row gap-2 w-full">
                    <Input
                        :id="fileInputId"
                        :key="resetKey"
                        type="file"
                        :accept="accept ?? uploadRules.accept"
                        :disabled="disabled"
                        class="cursor-pointer p-0 pr-3 file:mr-3 file:h-full file:cursor-pointer file:border-0 file:border-r file:border-custom-bg-dark file:bg-custom-bg-dark file:px-3 file:text-sm file:text-custom-shadow hover:file:bg-custom-secondary/20"
                        @change="onFileChange"
                    />

                    <div v-if="file || existingFileName" class="gap-2 flex flex-row w-fit">
                        <Button
                            v-if="file && canPreviewFile(file)"
                            type="button"
                            variant="float"
                            size="icon-text"
                            @click="openPreview"
                        >
                            <RiEyeLine class="shrink-0 h-4 w-4" />
                            <span class="hidden lg:flex">View file</span>
                        </Button>
                        <Button
                            v-if="file && !disabled"
                            type="button"
                            variant="float-red"
                            size="icon-text"
                            @click="clearFile"
                        >
                            <RiCloseLine class="shrink-0 h-4 w-4" />
                            <span class="hidden lg:flex">Remove</span>
                        </Button>
                    </div>
                </div>

                <p class="text-xs text-custom-shadow/70">{{ hint ?? defaultHint }}</p>

                <p v-if="note" class="text-xs text-custom-shadow/70">{{ note }}</p>
                <InputMessage variant="destructive" :message="fileError" class="mt-0" />
            </div>

            <div v-if="showDates" class="flex flex-col lg:flex-row gap-4 w-full">
                <div class="space-y-1 w-full">
                    <Label :for="`${fileInputId}-issued`">Issue Date</Label>
                    <Popover v-model:open="issuedPickerOpen">
                        <div class="flex">
                            <Input
                                :id="`${fileInputId}-issued`"
                                :model-value="issuedAt"
                                type="text"
                                inputmode="numeric"
                                maxlength="10"
                                placeholder="YYYY-MM-DD"
                                :disabled="disabled"
                                class="rounded-r-none"
                                @update:model-value="emit('update:issuedAt', String($event))"
                            />
                            <PopoverTrigger as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    :disabled="disabled"
                                    class="shrink-0 rounded-l-none border border-0 border-custom-bg-dark bg-custom-bg hover:bg-custom-secondary/20 dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
                                    aria-label="Choose issue date"
                                >
                                    <RiCalendarLine class="h-4 w-4" />
                                </Button>
                            </PopoverTrigger>
                        </div>
                        <PopoverContent align="start" class="w-auto p-0">
                            <CalendarPicker
                                :model-value="parseCalendarDate(issuedAt)"
                                initial-focus
                                @update:model-value="selectIssuedAt($event as CalendarDate | undefined)"
                            />
                        </PopoverContent>
                    </Popover>
                    <InputMessage variant="destructive" :message="issuedAtError" />
                </div>

                <div class="space-y-1 w-full">
                    <Label :for="`${fileInputId}-expires`">Expiration Date</Label>
                    <Popover v-model:open="expiresPickerOpen">
                        <div class="flex">
                            <Input
                                :id="`${fileInputId}-expires`"
                                :model-value="expiresAt"
                                type="text"
                                inputmode="numeric"
                                maxlength="10"
                                placeholder="YYYY-MM-DD"
                                :disabled="disabled"
                                class="rounded-r-none"
                                @update:model-value="emit('update:expiresAt', String($event))"
                            />
                            <PopoverTrigger as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    :disabled="disabled"
                                    class="shrink-0 rounded-l-none border border-0 border-custom-bg-dark bg-custom-bg hover:bg-custom-secondary/20 dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
                                    aria-label="Choose expiration date"
                                >
                                    <RiCalendarLine class="h-4 w-4" />
                                </Button>
                            </PopoverTrigger>
                        </div>
                        <PopoverContent align="start" class="w-auto p-0">
                            <CalendarPicker
                                :model-value="parseCalendarDate(expiresAt)"
                                initial-focus
                                @update:model-value="selectExpiresAt($event as CalendarDate | undefined)"
                            />
                        </PopoverContent>
                    </Popover>
                    <InputMessage variant="destructive" :message="expiresAtError" />
                </div>
            </div>
        </div>

        <Dialog v-model:open="previewOpen">
            <DialogContent class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden p-0">
                <DialogHeader class="border-b border-custom-bg-dark px-6 py-4 dark:border-custom-bg-light">
                    <DialogTitle class="truncate">{{ label }}</DialogTitle>
                    <DialogDescription v-if="file" class="truncate">
                        {{ file.name }} · {{ formatFileSize(file.size) }}
                    </DialogDescription>
                </DialogHeader>
                <div class="flex-1 overflow-auto bg-custom-bg-dark/10 p-4 dark:bg-custom-bg-light/10">
                    <div
                        v-if="file && previewUrl && previewType(file) === 'image'"
                        class="flex min-h-[55vh] items-center justify-center"
                    >
                        <img
                            :src="previewUrl"
                            :alt="file.name"
                            class="max-h-[70vh] w-auto max-w-full rounded-md border border-custom-bg-dark bg-white object-contain dark:border-custom-bg-light"
                        />
                    </div>
                    <div
                        v-else-if="file && previewUrl"
                        class="h-[70vh] overflow-hidden rounded-md border border-custom-bg-dark bg-white dark:border-custom-bg-light"
                    >
                        <iframe :src="previewUrl" class="h-full w-full" title="PDF preview" />
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
