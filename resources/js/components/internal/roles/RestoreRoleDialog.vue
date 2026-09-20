<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { restore } from '@/routes/roles';
import { router } from '@inertiajs/vue3';
import { RiRestartLine } from 'vue-remix-icons';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const open = defineModel<boolean>('open');

const props = defineProps<{
    role: {
        id: number;
        name: string;
    } | null;
}>();

const processing = ref(false);

function confirm() {
    if (!props.role) return;

    processing.value = true;

    router.patch(
        restore({ role: props.role.id }).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
            },
            onError: () => toast.error('Failed to restore role.'),
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
        title="Restore Role"
        confirm-label="Restore"
        processing-label="Restoring..."
        :icon="RiRestartLine"
        :processing="processing"
        @confirm="confirm"
    >
        <template #description>
            Are you sure you want to restore
            <span class="font-semibold text-custom-accent-3">{{ role?.name ?? 'this role' }}</span>?
            It will be moved back to the active roles list.
        </template>
    </ConfirmDialog>
</template>
