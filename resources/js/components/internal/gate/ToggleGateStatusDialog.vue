<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { toggleStatus } from '@/routes/gates';

import { RiShutDownLine } from 'vue-remix-icons';

type GateForToggle = {
    id: number;
    gate_name: string;
    status: 'active' | 'inactive';
};

const open = defineModel<boolean>('open');

const props = defineProps<{
    gate: GateForToggle | null;
}>();

const processing = ref(false);

function confirm() {
    if (!props.gate) return;

    processing.value = true;
    router.patch(
        toggleStatus(props.gate.id).url,
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
        title="Set Gate Status"
        :tone="gate?.status === 'active' ? 'negative' : 'primary'"
        :confirm-label="gate?.status === 'active' ? 'Inactivate' : 'Activate'"
        :icon="RiShutDownLine"
        :processing="processing"
        @confirm="confirm"
    >
        <template #description>
            Are you sure you want to set
            <span class="font-semibold text-custom-accent-3">{{ gate?.gate_name ?? 'this gate' }}</span>
            as {{ gate?.status === 'active' ? 'inactive' : 'active' }}?
        </template>
    </ConfirmDialog>
</template>
