<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { restore } from '@/actions/App/Http/Controllers/RouteController';
import { router } from '@inertiajs/vue3';
import { RiRestartLine } from 'vue-remix-icons';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const open = defineModel<boolean>('open');

const props = defineProps<{
    route: {
        id: number;
        route_name: string;
    } | null;
}>();

const processing = ref(false);

function confirm() {
    if (!props.route) return;

    processing.value = true;

    router.patch(
        restore(props.route.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
            },
            onError: () => toast.error('Failed to restore route.'),
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
        title="Restore Route"
        confirm-label="Restore"
        processing-label="Restoring..."
        :icon="RiRestartLine"
        :processing="processing"
        @confirm="confirm"
    >
        <template #description>
            Are you sure you want to restore
            <span class="font-semibold text-custom-accent-3">{{ route?.route_name ?? 'this route' }}</span>?
            It will be moved back to the active routes list.
        </template>
    </ConfirmDialog>
</template>
