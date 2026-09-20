<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { RiCheckLine } from 'vue-remix-icons';

const open = defineModel<boolean>('open');

const props = defineProps<{
    request: {
        requested_field: string;
        field_label?: string | null;
        dispatch?: { plate_number: string } | null;
    } | null;
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
        title="Approve Change Request"
        confirm-label="Confirm Approve"
        processing-label="Approving..."
        :icon="RiCheckLine"
        :processing="props.processing"
        @confirm="$emit('confirm')"
    >
        <template v-if="props.request" #description>
            Confirm approval for
            <span class="font-semibold text-custom-accent-3">{{ props.request.dispatch?.plate_number ?? 'this dispatch' }}</span>
            ·
            {{ props.request.field_label || formatFieldLabel(props.request.requested_field) }}
        </template>
    </ConfirmDialog>
</template>
