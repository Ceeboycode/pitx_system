<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { toggleStatus } from '@/routes/users';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
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
    <Dialog v-model:open="open">
        <DialogContent class="px-6">
            <DialogHeader class="px-0">
                <DialogTitle>Set user status</DialogTitle>
                <DialogDescription class="mt-4">
                    Are you sure you want to set
                    <span class="font-semibold text-custom-accent-3">{{
                        user?.name ?? 'this user'
                    }}</span>
                    as
                    <span class="font-semibold text-custom-accent-3">
                        {{ user?.status === 'active' ? 'inactive' : 'active' }} </span
                    >?
                </DialogDescription>
            </DialogHeader>
            <Separator class="mb-4" />
            <DialogFooter class="gap-2 sm:justify-end">
                <Button variant="ghost-outline" @click="open = false">
                    Cancel
                </Button>
                <Button
                    :variant="user?.status === 'active' ? 'destructive' : 'float-primary'"
                    @click="confirm"
                >
                    <RiShutDownLine class="h-4 w-4" />
                    {{ user?.status === 'active' ? 'Inactivate' : 'Activate' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
