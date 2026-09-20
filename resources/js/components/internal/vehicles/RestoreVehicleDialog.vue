<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { restore as restoreVehicle } from '@/routes/vehicles';
import { router } from '@inertiajs/vue3';
import { RiRestartLine } from 'vue-remix-icons';
import { ref } from 'vue';

const open = defineModel<boolean>('open');

const props = defineProps<{
    vehicle: {
        id: number;
        plate_number?: string | null;
        body_number?: string | null;
    };
}>();

const processing = ref(false);

function restore() {
    processing.value = true;

    router.post(
        restoreVehicle(props.vehicle.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
            },
            onFinish: () => (processing.value = false),
        },
    );
}
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        title="Restore Vehicle"
        tone="primary"
        confirm-label="Yes, Restore Vehicle"
        processing-label="Restoring..."
        :icon="RiRestartLine"
        :processing="processing"
        @confirm="restore"
    >
        <template #description>
            <span>You are about to restore</span>
            <span class="font-semibold text-custom-accent-3">{{
                props.vehicle.plate_number ?? `Vehicle #${props.vehicle.id}`
            }}</span>.
            <span class="text-muted-foreground">
                This vehicle will be moved back to the active list.
            </span>
        </template>
    </ConfirmDialog>
</template>
