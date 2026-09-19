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
    <Dialog v-model:open="open">
        <DialogContent class="max-w-md px-6" :show-close-button="false">
            <DialogHeader class="px-0">
                <DialogTitle>Restore Vehicle</DialogTitle>
                <DialogDescription>
                    <span>You are about to restore</span>
                    <span class="font-semibold text-custom-accent-3">{{
                        props.vehicle.plate_number ?? `Vehicle #${props.vehicle.id}`
                    }}</span>.
                    <span class="text-muted-foreground">
                        This vehicle will be moved back to the active list.
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
                    <RiRestartLine class="h-4 w-4" />
                    {{ processing ? 'Restoring...' : 'Yes, Restore Vehicle' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
