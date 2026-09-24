<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { destroy } from '@/routes/employee-users';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

import { RiArchive2Line } from 'vue-remix-icons';

const open = defineModel<boolean>('open');

const props = defineProps<{
    employee: {
        id: number;
        name: string;
    } | null;
}>();

const processing = ref(false);

function archive() {
    if (!props.employee) return;

    processing.value = true;

    router.delete(destroy(props.employee.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
        onFinish: () => {
            processing.value = false;
        },
        onError: () => toast.error('Failed to archive employee.'),
    });
}
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        title="Archive Employee"
        tone="negative"
        confirm-label="Archive Employee"
        processing-label="Archiving..."
        :icon="RiArchive2Line"
        :processing="processing"
        @confirm="archive"
    >
        <template #description>
            Are you sure you want to archive
            <span class="font-semibold text-custom-accent-3">{{ employee?.name ?? 'this employee' }}</span>?
        </template>
    </ConfirmDialog>
</template>
