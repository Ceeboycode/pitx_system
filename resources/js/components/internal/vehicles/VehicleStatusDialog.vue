<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { toggleStatus } from '@/routes/vehicles';
import { humanize } from '@/lib/format';

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
import { Textarea } from '@/components/ui/textarea';
import { RiOctagonLine, RiSpam2Line } from 'vue-remix-icons';

type VehicleForStatus = {
    id: number;
    plate_number: string | null;
};

type TargetStatus = 'active' | 'inactive' | 'suspended';

const open = defineModel<boolean>('open');

const props = defineProps<{
    vehicle: VehicleForStatus | null;
    targetStatus: TargetStatus;
}>();

const suspendRemarks = ref('');
const isSuspending = computed(() => props.targetStatus === 'suspended');

const statusActionLabel = (status: string) =>
    status === 'active'
        ? 'Set Active'
        : status === 'inactive'
          ? 'Set Inactive'
          : 'Suspend';

watch(open, (isOpen) => {
    if (isOpen) suspendRemarks.value = '';
});

function confirm() {
    if (!props.vehicle) return;

    router.patch(
        toggleStatus(props.vehicle.id).url,
        {
            status: props.targetStatus,
            suspension_remark: isSuspending.value
                ? suspendRemarks.value
                : undefined,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
                suspendRemarks.value = '';
            },
        },
    );
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-w-md px-6">
            <DialogHeader class="px-0">
                <DialogTitle>{{ statusActionLabel(targetStatus) }}</DialogTitle>
                <DialogDescription>
                    <span class="block">
                        {{ isSuspending ? 'Provide a reason to suspend' : 'This will set' }}
                        <span class="font-semibold text-custom-accent-3">{{ vehicle?.plate_number || 'this vehicle' }}</span>.
                        {{ isSuspending ? 'The suspension reason is stored separately.' : `New status: ${humanize(targetStatus)}.` }}
                    </span>
                </DialogDescription>
            </DialogHeader>
            <form class="space-y-3" @submit.prevent="confirm">
                <div v-if="isSuspending" class="flex flex-col">
                    <Textarea
                        v-model="suspendRemarks"
                        class="min-h-24 border-custom-bg-dark bg-custom-bg p-3 text-sm text-custom-shadow placeholder:text-custom-shadow/50 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:bg-white dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
                        rows="3"
                        placeholder="Brief reason for suspension..."
                    />
                </div>

                <Separator />
                <DialogFooter class="gap-2 sm:justify-end">
                    <DialogClose as-child>
                        <Button type="button" variant="ghost-outline">Cancel</Button>
                    </DialogClose>
                    <Button
                        type="submit"
                        :variant="isSuspending ? 'destructive' : 'float-primary'"
                        :disabled="isSuspending && !suspendRemarks.trim()"
                    >
                        <RiSpam2Line v-if="isSuspending" class="h-4 w-4" />
                        <RiOctagonLine v-else class="h-4 w-4" />
                        {{ statusActionLabel(targetStatus) }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
