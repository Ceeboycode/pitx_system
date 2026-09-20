<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';

import { reject } from '@/routes/companies/documents';

const open = defineModel<boolean>('open');

const props = defineProps<{
    documentId: number | null;
    companyId: number;
}>();

const emit = defineEmits<{ done: [] }>();

const remarkPresets = [
    {
        value: 'missing_signature',
        label: 'Missing signature',
        text: 'Missing signature. Please upload a signed copy.',
    },
    {
        value: 'blurred',
        label: 'Blurred / unreadable file',
        text: 'The file is blurred or unreadable. Please upload a clearer scan or photo.',
    },
    {
        value: 'wrong_document',
        label: 'Wrong document uploaded',
        text: 'Wrong document uploaded. Please upload the correct document.',
    },
    {
        value: 'expired',
        label: 'Expired document',
        text: 'Document appears expired. Please upload a valid or updated document.',
    },
    {
        value: 'mismatch_name',
        label: 'Company name mismatch',
        text: 'Company name does not match our records. Please upload the correct document.',
    },
    {
        value: 'mismatch_details',
        label: 'Details mismatch / incomplete',
        text: 'Some details are missing or do not match. Please review and re-upload.',
    },
    {
        value: 'needs_stamp',
        label: 'Missing stamp / seal',
        text: 'Missing stamp or seal. Please upload a stamped or sealed copy.',
    },
    {
        value: 'missing_pages',
        label: 'Missing pages / incomplete scan',
        text: 'Incomplete document (missing pages). Please upload the complete file.',
    },
    {
        value: 'reupload_pdf',
        label: 'Please re-upload as PDF',
        text: 'Please re-upload the document as a PDF for verification.',
    },
] as const;

type RemarkPresetValue = (typeof remarkPresets)[number]['value'];

const selectedPresets = ref<RemarkPresetValue[]>([]);
const rejectForm = useForm<{ remarks: string }>({ remarks: '' });

watch(open, (isOpen) => {
    if (isOpen) {
        selectedPresets.value = [];
        rejectForm.reset();
        rejectForm.clearErrors();
    }
});

function togglePreset(value: RemarkPresetValue) {
    const preset = remarkPresets.find((p) => p.value === value)!;
    const isSelected = selectedPresets.value.includes(value);

    if (isSelected) {
        selectedPresets.value = selectedPresets.value.filter((v) => v !== value);
        const lines = rejectForm.remarks
            .split('\n')
            .filter((line) => !line.startsWith(preset.text));
        rejectForm.remarks = lines.join('\n').trim();
    } else {
        selectedPresets.value = [...selectedPresets.value, value];
        const current = rejectForm.remarks.trim();
        rejectForm.remarks = current ? current + '\n' + preset.text : preset.text;
    }
}

function closeReject() {
    open.value = false;
    selectedPresets.value = [];
    rejectForm.reset();
    rejectForm.clearErrors();
}

function submitReject() {
    if (!props.documentId || rejectForm.processing) return;
    rejectForm.patch(
        reject({ company: props.companyId, document: props.documentId }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                closeReject();
                emit('done');
            },
        },
    );
}
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        title="Mark as Invalid"
        tone="negative"
        size="lg"
        confirm-label="Mark as Invalid"
        processing-label="Invalidating..."
        :processing="rejectForm.processing"
        :confirm-disabled="!rejectForm.remarks.trim()"
        @confirm="submitReject"
    >
        <template #description>
            Select one or more reasons below.
        </template>

        <div class="space-y-4">

            <div>
                <p
                    class="mb-2.5 text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                >
                    Reasons
                </p>
                <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                    <div
                        v-for="preset in remarkPresets"
                        :key="preset.value"
                        :class="[
                            'flex cursor-pointer items-center gap-3 rounded-lg border px-3 py-2.5 text-sm transition-colors',
                            selectedPresets.includes(preset.value)
                                ? 'border-rose-300 bg-rose-50 text-rose-700'
                                : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
                        ]"
                        @click="togglePreset(preset.value)"
                    >
                        <span
                            :class="[
                                'flex h-4 w-4 shrink-0 items-center justify-center rounded-sm border-2 transition-colors',
                                selectedPresets.includes(preset.value)
                                    ? 'border-rose-600 bg-rose-600'
                                    : 'border-slate-400 bg-white',
                            ]"
                        >
                            <svg
                                v-if="selectedPresets.includes(preset.value)"
                                class="h-3 w-3 text-white"
                                viewBox="0 0 12 12"
                                fill="none"
                            >
                                <path
                                    d="M2 6l3 3 5-5"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>
                        <span class="leading-snug">{{
                            preset.label
                        }}</span>
                    </div>
                </div>

                <p
                    v-if="selectedPresets.length > 0"
                    class="mt-2 text-xs font-medium text-rose-600"
                >
                    {{ selectedPresets.length }} reason{{
                        selectedPresets.length > 1 ? 's' : ''
                    }}
                    selected
                </p>
            </div>

            <div class="space-y-1.5">
                <Label
                    class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                >
                    Remarks <span class="text-rose-500">*</span>
                </Label>
                <Textarea
                    v-model="rejectForm.remarks"
                    placeholder="Select reasons above or write your own..."
                    class="min-h-25 rounded-lg text-sm"
                />
                <InputError
                    class="mt-1"
                    :message="rejectForm.errors.remarks"
                />
            </div>
        </div>
    </ConfirmDialog>
</template>
