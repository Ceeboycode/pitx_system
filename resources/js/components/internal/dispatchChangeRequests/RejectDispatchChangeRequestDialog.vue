<script setup lang="ts">
import { InputMessage } from '@/components/ui/_input-message';
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { RiCloseCircleLine } from 'vue-remix-icons';

const open = defineModel<boolean>('open');
const reason = defineModel<string>('reason', { default: '' });

const props = defineProps<{
    request: {
        requested_field: string;
        field_label?: string | null;
        dispatch?: { plate_number: string } | null;
    } | null;
    error?: string;
    processing?: boolean;
}>();

defineEmits<{ confirm: [] }>();

function formatFieldLabel(field: string): string {
    return field.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        title="Reject Change Request"
        tone="negative"
        size="lg"
        confirm-label="Reject Request"
        processing-label="Rejecting..."
        :icon="RiCloseCircleLine"
        :processing="props.processing"
        @confirm="$emit('confirm')"
    >
        <template v-if="props.request" #description>
            <span class="font-semibold text-custom-accent-3">{{ props.request.dispatch?.plate_number }}</span>
            ·
            {{ props.request.field_label || formatFieldLabel(props.request.requested_field) }}
        </template>

        <div class="space-y-2">
            <Label for="rejection_reason">Reason for Rejection</Label>
            <Textarea
                id="rejection_reason"
                v-model="reason"
                rows="3"
                placeholder="Explain why this request is being rejected..."
            />
            <InputMessage variant="destructive" :message="props.error" />
        </div>
    </ConfirmDialog>
</template>
