<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { forceDelete } from '@/routes/vehicle-types';
import { router } from '@inertiajs/vue3';
import { RiDeleteBin7Line } from 'vue-remix-icons';
import { ref } from 'vue';

const open = defineModel<boolean>('open');

const props = defineProps<{
    vehicleType: {
        id: number;
        type_name: string;
    };
}>();

const processing = ref(false);

function deletePermanently() {
    processing.value = true;

    router.delete(forceDelete(props.vehicleType.id).url, {
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
        title="Delete Vehicle Type Permanently"
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
            <span class="font-semibold text-custom-accent-3">{{ props.vehicleType.type_name }}</span>
            and remove it from the system.
        </template>
    </ConfirmDialog>
</template>
