<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { resetPassword } from '@/routes/users';

import { RiKey2Line } from 'vue-remix-icons';

type UserForReset = {
    id: number;
    name: string;
};

const open = defineModel<boolean>('open');

const props = defineProps<{
    user: UserForReset | null;
}>();

function confirm() {
    if (!props.user) return;

    router.post(
        resetPassword(props.user.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
            },
            onError: () => toast.error('Failed to reset password.'),
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
        :icon="RiKey2Line"
        @confirm="confirm"
    >
        <template #description>
            Generate a temporary password for
            <span class="font-semibold text-custom-accent-3">{{
                user?.name ?? 'this user'
            }}</span
            > and email it to their registered address.
        </template>
    </ConfirmDialog>
</template>
