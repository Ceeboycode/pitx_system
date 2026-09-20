<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { Textarea } from '@/components/ui/textarea';
import { RiCloseCircleLine } from 'vue-remix-icons';

const open = defineModel<boolean>('open');
const reason = defineModel<string>('reason', { default: '' });

const props = defineProps<{
    error?: string;
    processing?: boolean;
    disabled?: boolean;
}>();

defineEmits<{ confirm: [] }>();
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        title="Reject Change Request"
        description="This will be visible to the requester."
        tone="negative"
        size="lg"
        confirm-label="Reject"
        :icon="RiCloseCircleLine"
        :processing="props.processing"
        :confirm-disabled="props.disabled"
        @confirm="$emit('confirm')"
    >
        <div class="space-y-2">
            <Textarea
                v-model="reason"
                rows="4"
                placeholder="Explain why this request is being rejected..."
                class="resize-none"
            />
            <p v-if="props.error" class="text-xs text-destructive">{{ props.error }}</p>
        </div>
    </ConfirmDialog>
</template>
