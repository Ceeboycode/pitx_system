<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { destroy } from '@/routes/gates';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import { RiArchive2Line } from 'vue-remix-icons';

const open = defineModel<boolean>('open');

const props = defineProps<{
    gate: {
        id: number;
        gate_name: string;
    } | null;
}>();

const processing = ref(false);

function archive() {
    if (!props.gate) return;

    processing.value = true;

    router.delete(destroy(props.gate.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        title="Archive Gate"
        tone="negative"
        confirm-label="Archive"
        processing-label="Archiving..."
        :icon="RiArchive2Line"
        :processing="processing"
        @confirm="archive"
    >
        <template #description>
            Are you sure you want to archive
            <span class="font-semibold text-custom-accent-3">{{
                gate?.gate_name || 'this gate'
            }}</span>? You can restore it later from the Trash.
        </template>
    </ConfirmDialog>
</template>
