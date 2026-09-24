<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { resetPassword } from '@/routes/employee-users';

import { RiShieldKeyholeLine } from 'vue-remix-icons';

type EmployeeForReset = {
    id: number;
    name: string;
};

const open = defineModel<boolean>('open');

const props = defineProps<{
    employee: EmployeeForReset | null;
}>();

const processing = ref(false);

function confirm() {
    if (!props.employee) return;

    processing.value = true;
    router.patch(
        resetPassword(props.employee.id).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
            onSuccess: () => {
                open.value = false;
            },
            onError: () => toast.error('Failed to reset employee password.'),
        },
    );
}
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        title="Reset Password"
        tone="primary"
        confirm-label="Reset Password"
        processing-label="Resetting..."
        :icon="RiShieldKeyholeLine"
        :processing="processing"
        @confirm="confirm"
    >
        <template #description>
            Are you sure you want to reset the password for
            <span class="font-semibold text-custom-accent-3">{{ employee?.name ?? 'this employee' }}</span>?
        </template>
    </ConfirmDialog>
</template>
