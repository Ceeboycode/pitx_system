<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { forceDelete } from '@/routes/vehicles';
import { router } from '@inertiajs/vue3';
import { RiDeleteBin7Line } from 'vue-remix-icons';
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

function deletePermanently() {
    if (processing.value) return;

    processing.value = true;

    router.delete(forceDelete({ vehicle: props.vehicle.id }).url, {
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
        title="Delete Vehicle Permanently"
        tone="negative"
        confirm-label="Delete Permanently"
        processing-label="Deleting..."
        confirm-text="DELETE"
        :icon="RiDeleteBin7Line"
        :processing="processing"
        @confirm="deletePermanently"
    >
        <template #description>
            This action cannot be undone. It will permanently delete
            <span class="font-semibold text-custom-accent-3">{{
                props.vehicle.plate_number ??
                props.vehicle.body_number ??
                `Vehicle #${props.vehicle.id}`
            }}</span>
            and remove it from the system.
        </template>
    </ConfirmDialog>
</template>
