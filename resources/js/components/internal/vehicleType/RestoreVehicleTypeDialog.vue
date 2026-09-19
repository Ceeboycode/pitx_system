<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { Button } from '@/components/ui/button';
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
    <Dialog v-model:open="open">
        <DialogContent class="max-w-md px-6" :show-close-button="false">
            <DialogHeader class="px-0">
                <DialogTitle>Restore Vehicle Type</DialogTitle>
                <DialogDescription>
                    <span>You are about to restore</span>
                    <span class="font-semibold text-custom-accent-3">{{
                        vehicleType?.type_name ?? `Vehicle Type #${vehicleType?.id}`
                    }}</span>.
                    <span class="text-muted-foreground">
                        It will be moved back to the vehicle types list.
                    </span>
                </DialogDescription>
            </DialogHeader>
            <Separator />
            <DialogFooter class="pt-3 gap-2 sm:justify-end">
                <Button
                    variant="ghost-outline"
                    :disabled="processing"
                    @click="open = false"
                >
                    Cancel
                </Button>
                <Button
                    variant="float-primary"
                    :disabled="processing"
                    @click="restore"
                >
                    <RiRestartLine class="h-4 w-4 shrink-0" />
                    {{ processing ? 'Restoring...' : 'Yes, Restore Vehicle Type' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
