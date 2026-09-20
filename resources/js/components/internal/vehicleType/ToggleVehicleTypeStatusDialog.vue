<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { toggleStatus } from '@/routes/vehicle-types';

import { RiShutDownLine } from 'vue-remix-icons';

type VehicleTypeForToggle = {
    id: number;
    type_name: string;
    is_active: boolean;
};

const open = defineModel<boolean>('open');

const props = defineProps<{
    vehicle_type: VehicleTypeForToggle | null;
}>();

const processing = ref(false);

function confirm() {
    if (!props.vehicle_type) return;

    processing.value = true;
    router.patch(
        toggleStatus(props.vehicle_type.id).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
                open.value = false;
            },
        },
    );
}
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        title="Set Vehicle Type Status"
        :tone="vehicle_type?.is_active ? 'negative' : 'primary'"
        :confirm-label="vehicle_type?.is_active ? 'Inactivate' : 'Activate'"
        :icon="RiShutDownLine"
        :processing="processing"
        @confirm="confirm"
    >
        <template #description>
            Are you sure you want to set
            <span class="font-semibold text-custom-accent-3">{{ vehicle_type?.type_name ?? 'this vehicle type' }}</span>
            as {{ vehicle_type?.is_active ? 'inactive' : 'active' }}?
        </template>
    </ConfirmDialog>
</template>
