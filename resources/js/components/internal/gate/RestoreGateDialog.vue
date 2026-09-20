<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { restore } from '@/routes/gates';
import { router } from '@inertiajs/vue3';
import { RiRestartLine } from 'vue-remix-icons';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const open = defineModel<boolean>('open');

const props = defineProps<{
    gate: {
        id: number;
        gate_name: string;
    } | null;
}>();

const processing = ref(false);

function confirm() {
    if (!props.gate) return;

    processing.value = true;

    router.post(
        restore(props.gate.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
            },
            onError: () => toast.error('Failed to restore gate.'),
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        title="Restore Gate"
        confirm-label="Restore"
        processing-label="Restoring..."
        :icon="RiRestartLine"
        :processing="processing"
        @confirm="confirm"
    >
        <template #description>
            Are you sure you want to restore
            <span class="font-semibold text-custom-accent-3">{{ gate?.gate_name ?? 'this gate' }}</span>?
            It will be moved back to the active gates list.
        </template>
    </ConfirmDialog>
</template>
