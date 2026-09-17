<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { toggleStatus } from '@/routes/vehicle-types';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import Separator from '@/components/ui/separator/Separator.vue';
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
    <Dialog v-model:open="open">
        <DialogContent class="px-6">
            <DialogHeader class="px-0">
                <DialogTitle>Set vehicle type status</DialogTitle>
                <DialogDescription class="mt-4">
                    Are you sure you want to set
                    <span class="font-semibold text-custom-accent-3">{{ vehicle_type?.type_name ?? 'this vehicle type' }}</span>
                    as {{ vehicle_type?.is_active ? 'inactive' : 'active' }}?
                </DialogDescription>
            </DialogHeader>
            <Separator class="mb-4" />
            <DialogFooter class="gap-2 sm:justify-end">
                <Button variant="ghost-outline" :disabled="processing" @click="open = false">Cancel</Button>
                <Button
                    :variant="vehicle_type?.is_active ? 'destructive' : 'float-primary'"
                    :disabled="processing"
                    @click="confirm"
                >
                    <RiShutDownLine class="h-4 w-4" />
                    {{ vehicle_type?.is_active ? 'Inactivate' : 'Activate' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

