<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { router } from '@inertiajs/vue3';
import { destroy } from '@/routes/vehicles';

import { RiArchive2Line } from 'vue-remix-icons';

type VehicleForArchive = {
    id: number;
    plate_number?: string | null;
};

const open = defineModel<boolean>('open');

const props = defineProps<{
    vehicle: VehicleForArchive | null;
}>();

function confirm() {
    if (!props.vehicle) return;

    router.delete(destroy({ vehicle: props.vehicle.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        title="Archive Vehicle"
        tone="negative"
        confirm-label="Archive"
        :icon="RiArchive2Line"
        @confirm="confirm"
    >
        <template #description>
            You are about to archive
            <span class="font-semibold text-custom-accent-3">{{ vehicle?.plate_number || 'this vehicle' }}</span>. You can restore it later from Archived Vehicles.
        </template>
    </ConfirmDialog>
</template>
