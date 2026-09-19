<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { destroy } from '@/routes/vehicles';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { RiArchive2Line } from 'vue-remix-icons';

type VehicleForArchive = {
    id: number;
    plate_number: string | null;
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
    <Dialog v-model:open="open">
        <DialogContent class="max-w-md px-6" :show-close-button="false">
            <DialogHeader class="px-0">
                <DialogTitle>Archive Vehicle</DialogTitle>
                <DialogDescription>
                    You are about to archive
                    <span class="font-semibold text-custom-accent-3">{{ vehicle?.plate_number || 'this vehicle' }}</span>. You can restore it later from Archived Vehicles.
                </DialogDescription>
            </DialogHeader>
            <Separator />
            <DialogFooter class="pt-3 gap-2 sm:justify-end">
                <DialogClose as-child>
                    <Button type="button" variant="ghost-outline">Cancel</Button>
                </DialogClose>
                <Button type="button" variant="destructive" @click="confirm">
                    <RiArchive2Line class="h-4 w-4 shrink-0" />
                    Archive
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
