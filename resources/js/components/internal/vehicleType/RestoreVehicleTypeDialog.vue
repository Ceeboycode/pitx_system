<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { restore as restoreVehicleType } from '@/routes/vehicle-types';
import { router } from '@inertiajs/vue3';
import { RiRestartLine } from 'vue-remix-icons';
import { ref } from 'vue';

const open = defineModel<boolean>('open');

const props = defineProps<{
    vehicleType: {
        id: number;
        type_name?: string | null;
    } | null;
}>();

const processing = ref(false);

function restore() {
    if (!props.vehicleType) return;

    processing.value = true;

    router.post(
        restoreVehicleType(props.vehicleType.id).url,
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
        title="Restore Vehicle Type"
        tone="primary"
        confirm-label="Yes, Restore Vehicle Type"
        processing-label="Restoring..."
        :icon="RiRestartLine"
        :processing="processing"
        @confirm="restore"
    >
        <template #description>
            <span>You are about to restore</span>
            <span class="font-semibold text-custom-accent-3">{{
                vehicleType?.type_name ?? `Vehicle Type #${vehicleType?.id}`
            }}</span>.
            <span class="text-muted-foreground">
                It will be moved back to the vehicle types list.
            </span>
        </template>
    </ConfirmDialog>
</template>
