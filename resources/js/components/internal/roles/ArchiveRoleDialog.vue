<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { destroy } from '@/routes/roles';

import { RiArchive2Line } from 'vue-remix-icons';

type RoleForArchive = {
    id: number;
    name: string;
};

const open = defineModel<boolean>('open');

const props = defineProps<{
    role: RoleForArchive | null;
}>();

const processing = ref(false);

function archive() {
    if (processing.value || !props.role) return;

    processing.value = true;
    router.delete(destroy({ role: props.role.id }).url, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            open.value = false;
        },
    });
}
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        title="Archive Role"
        tone="negative"
        confirm-label="Archive"
        processing-label="Archiving..."
        :icon="RiArchive2Line"
        :processing="processing"
        @confirm="archive"
    >
        <template #description>
            Are you sure you want to archive
            <span class="font-semibold text-custom-accent-3">{{ role?.name }}</span>?
            It can be restored later.
        </template>
    </ConfirmDialog>
</template>
