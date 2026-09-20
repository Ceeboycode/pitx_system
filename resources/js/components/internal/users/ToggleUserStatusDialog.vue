<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { toggleStatus } from '@/routes/users';

import { RiShutDownLine } from 'vue-remix-icons';

type UserForToggle = {
    id: number;
    name: string;
    status?: string | null;
};

const open = defineModel<boolean>('open');

const props = defineProps<{
    user: UserForToggle | null;
}>();

function confirm() {
    if (!props.user) return;

    router.put(
        toggleStatus(props.user.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
            },
            onError: () => toast.error('Failed to update user status.'),
        },
    );
}
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        title="Set User Status"
        :tone="user?.status === 'active' ? 'negative' : 'primary'"
        :confirm-label="user?.status === 'active' ? 'Inactivate' : 'Activate'"
        :icon="RiShutDownLine"
        @confirm="confirm"
    >
        <template #description>
            Are you sure you want to set
            <span class="font-semibold text-custom-accent-3">{{
                user?.name ?? 'this user'
            }}</span>
            as
            <span class="font-semibold text-custom-accent-3">
                {{ user?.status === 'active' ? 'inactive' : 'active' }} </span
            >?
        </template>
    </ConfirmDialog>
</template>
