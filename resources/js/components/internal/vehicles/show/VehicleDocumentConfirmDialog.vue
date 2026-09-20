<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { humanize } from '@/lib/format';
import { useForm } from '@inertiajs/vue3';

const open = defineModel<boolean>('open');

const props = defineProps<{
    doc: {
        id: number;
        document_type: string;
    } | null;
    action: 'verify' | 'unverify';
    vehicleId: number;
}>();

const emit = defineEmits<{ done: [] }>();

const actionForm = useForm({});

function confirm() {
    if (!props.doc || actionForm.processing) return;

    actionForm.patch(
        `/vehicles/${props.vehicleId}/documents/${props.doc.id}/${props.action}`,
        {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
                emit('done');
            },
        },
    );
}
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        :title="action === 'verify' ? 'Verify Document' : 'Move Back to Pending'"
        :tone="action === 'verify' ? 'primary' : 'negative'"
        confirm-label="Confirm"
        processing-label="Processing..."
        :processing="actionForm.processing"
        @confirm="confirm"
    >
        <template #description>
            {{ action === 'verify' ? 'This will mark' : 'This will revert' }}
            <span class="font-semibold text-custom-accent-3">{{ humanize(doc?.document_type) }}</span>
            {{ action === 'verify' ? 'as verified.' : 'back to pending review.' }}
        </template>
    </ConfirmDialog>
</template>
