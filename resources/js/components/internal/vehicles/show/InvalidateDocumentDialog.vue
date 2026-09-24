<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

import { InputMessage } from '@/components/ui/_input-message';
import { Checkbox } from '@/components/ui/checkbox';
import Label from '@/components/ui/label/Label.vue';
import { Textarea } from '@/components/ui/textarea';

type VehicleDocument = {
    id: number;
    document_type: string;
    remarks?: string | null;
};

const open = defineModel<boolean>('open');

const props = defineProps<{
    doc: VehicleDocument | null;
    vehicleId: number;
}>();

const invalidPresets = [
    {
        value: 'blurred',
        label: 'Blurred or unreadable file',
        text: 'The uploaded document is blurred or unreadable. Please upload a clearer copy.',
    },
    {
        value: 'expired',
        label: 'Expired document',
        text: 'The uploaded document is already expired. Please upload a valid updated copy.',
    },
    {
        value: 'wrong_document',
        label: 'Wrong document uploaded',
        text: 'The uploaded file is the wrong document. Please upload the correct requirement.',
    },
    {
        value: 'missing_pages',
        label: 'Missing pages or incomplete scan',
        text: 'The uploaded document is incomplete or has missing pages. Please upload the full copy.',
    },
    {
        value: 'mismatch',
        label: 'Vehicle details do not match',
        text: 'The document details do not match the assigned vehicle information. Please review and re-upload.',
    },
    {
        value: 'reupload_pdf',
        label: 'Please upload as PDF',
        text: 'Please re-upload this requirement as a PDF file.',
    },
] as const;

type InvalidPresetValue = (typeof invalidPresets)[number]['value'];

const selectedInvalidPresets = ref<InvalidPresetValue[]>([]);

const form = useForm<{ remarks: string }>({ remarks: '' });

function humanize(text?: string | null) {
    if (!text) return '—';
    return text.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

watch(
    selectedInvalidPresets,
    (values) => {
        const lines = values
            .map(
                (value) =>
                    invalidPresets.find((preset) => preset.value === value)
                        ?.text ?? '',
            )
            .filter(Boolean);

        form.remarks = lines.join('\n');
    },
    { deep: true },
);

watch(open, (isOpen) => {
    if (isOpen) {
        selectedInvalidPresets.value = [];
        form.reset();
        form.clearErrors();
        form.remarks = props.doc?.remarks ?? '';
    }
});

function toggleInvalidPreset(value: InvalidPresetValue) {
    const exists = selectedInvalidPresets.value.includes(value);

    if (exists) {
        selectedInvalidPresets.value = selectedInvalidPresets.value.filter(
            (item) => item !== value,
        );
        return;
    }

    selectedInvalidPresets.value = [...selectedInvalidPresets.value, value];
}

function submit() {
    if (!props.doc || form.processing) return;

    form.patch(
        `/vehicles/${props.vehicleId}/documents/${props.doc.id}/invalidate`,
        {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
                selectedInvalidPresets.value = [];
                form.reset();
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
        confirm-label="Mark Invalid"
        processing-label="Submitting..."
        :processing="form.processing"
        :confirm-disabled="!form.remarks.trim()"
        @confirm="submit"
    >
        <template #description>
            Select one or more reasons below. The remarks field will be
            built automatically — you can still edit it before
            submitting for
            <span class="font-semibold text-foreground">
                {{ humanize(doc?.document_type) }} </span
            >.
        </template>

        <div class="space-y-4">
            <div>
                <p
                    class="mb-2.5 text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                >
                    Reasons
                </p>

                <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                    <label
                        v-for="preset in invalidPresets"
                        :key="preset.value"
                        :class="[
                            'flex cursor-pointer items-center gap-3 rounded-lg border px-3 py-2.5 text-sm transition-colors',
                            selectedInvalidPresets.includes(preset.value)
                                ? 'border-rose-300 bg-rose-50 text-rose-700'
                                : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
                        ]"
                        @click="toggleInvalidPreset(preset.value)"
                    >
                        <Checkbox
                            :checked="
                                selectedInvalidPresets.includes(preset.value)
                            "
                            :class="
                                selectedInvalidPresets.includes(preset.value)
                                    ? 'border-rose-400 data-[state=checked]:border-rose-600 data-[state=checked]:bg-rose-600'
                                    : ''
                            "
                            @click.stop
                            @update:checked="toggleInvalidPreset(preset.value)"
                        />
                        <span class="leading-snug">{{ preset.label }}</span>
                    </label>
                </div>

                <p
                    v-if="selectedInvalidPresets.length > 0"
                    class="mt-2 text-xs font-semibold text-rose-600"
                >
                    {{ selectedInvalidPresets.length }}
                    reason{{ selectedInvalidPresets.length > 1 ? 's' : '' }}
                    selected
                </p>
            </div>

            <div class="space-y-1.5">
                <Label
                    for="inv-remarks"
                    class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                >
                    Remarks <span class="text-destructive">*</span>
                </Label>

                <Textarea
                    id="inv-remarks"
                    v-model="form.remarks"
                    placeholder="Select reasons above or write your own..."
                    class="min-h-[100px] rounded-lg text-sm"
                />

                <InputMessage variant="destructive" class="mt-1" :message="form.errors.remarks" />

                <p class="text-[11px] text-muted-foreground">
                    You can edit the auto-generated text or write your own
                    remarks.
                </p>
            </div>
        </div>
    </ConfirmDialog>
</template>
