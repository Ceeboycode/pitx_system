<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { destroy } from '@/actions/App/Http/Controllers/RouteController';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import { RiArchive2Line } from 'vue-remix-icons';

const open = defineModel<boolean>('open');

const props = defineProps<{
    route: {
        id: number;
        route_name: string;
    } | null;
}>();

const processing = ref(false);

function archive() {
    if (!props.route) return;

    processing.value = true;

    router.delete(destroy(props.route.id).url, {
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
        title="Archive Route"
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
                route?.route_name || 'this route'
            }}</span
            >? This action will remove it from active records.
        </template>
    </ConfirmDialog>
</template>
