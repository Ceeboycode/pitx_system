<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { destroy } from '@/routes/users';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import { RiArchive2Line } from 'vue-remix-icons';

const open = defineModel<boolean>('open');

const props = defineProps<{
    user: {
        id: number;
        name: string;
    } | null;
}>();

const processing = ref(false);

function archive() {
    if (!props.user) return;

    processing.value = true;

    router.delete(destroy(props.user.id).url, {
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
        title="Archive User"
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
                user?.name || 'this user'
            }}</span>? You can restore this account from Archived Users.
        </template>
    </ConfirmDialog>
</template>
