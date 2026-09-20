<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { toggleStatus } from '@/routes/vehicles';
import { humanize } from '@/lib/format';

import { Textarea } from '@/components/ui/textarea';
import { RiShutDownLine, RiSpam2Line } from 'vue-remix-icons';

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
        ? 'Activate'
        : status === 'inactive'
          ? 'Inactivate'
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
    <ConfirmDialog
        v-model:open="open"
        :title="statusActionLabel(targetStatus)"
        :tone="targetStatus === 'active' ? 'primary' : 'negative'"
        :confirm-label="statusActionLabel(targetStatus)"
        :icon="isSuspending ? RiSpam2Line : RiShutDownLine"
        :confirm-disabled="isSuspending && !suspendRemarks.trim()"
        @confirm="confirm"
    >
        <template #description>
            {{ isSuspending ? 'Provide a reason to suspend' : 'This will set' }}
            <span class="font-semibold text-custom-accent-3">{{ vehicle?.plate_number || 'this vehicle' }}</span>.
            {{ isSuspending ? 'The suspension reason is stored separately.' : `New status: ${humanize(targetStatus)}.` }}
        </template>

        <template v-if="isSuspending" #default>
            <Textarea
                v-model="suspendRemarks"
                class="min-h-24 border-custom-bg-dark bg-custom-bg p-3 text-sm text-custom-shadow placeholder:text-custom-shadow/50 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:bg-white dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
                rows="3"
                placeholder="Brief reason for suspension..."
            />
        </template>
    </ConfirmDialog>
</template>
