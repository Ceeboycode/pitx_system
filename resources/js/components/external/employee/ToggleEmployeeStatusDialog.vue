<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { toggleStatus } from '@/routes/employee-users';

import { RiShutDownLine } from 'vue-remix-icons';

type EmployeeForToggle = {
    id: number;
    name: string;
    status: string;
};

const open = defineModel<boolean>('open');

const props = defineProps<{
    employee: EmployeeForToggle | null;
}>();

const processing = ref(false);

function confirm() {
    if (!props.employee) return;

    processing.value = true;
    router.patch(
        toggleStatus(props.employee.id).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
            onSuccess: () => {
                open.value = false;
            },
            onError: () => toast.error('Failed to update employee status.'),
        },
    );
}
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        :title="employee?.status === 'active' ? 'Inactivate Employee' : 'Activate Employee'"
        :tone="employee?.status === 'active' ? 'negative' : 'primary'"
        :confirm-label="employee?.status === 'active' ? 'Inactivate' : 'Activate'"
        processing-label="Updating..."
        :icon="RiShutDownLine"
        :processing="processing"
        @confirm="confirm"
    >
        <template #description>
            Are you sure you want to set
            <span class="font-semibold text-custom-accent-3">{{ employee?.name ?? 'this employee' }}</span>
            as {{ employee?.status === 'active' ? 'inactive' : 'active' }}?
        </template>
    </ConfirmDialog>
</template>
