<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { resetPassword } from '@/routes/users';

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
    <Dialog v-model:open="open">
        <DialogContent class="max-w-md px-6" :show-close-button="false">
            <DialogHeader class="px-0">
                <DialogTitle>Reset Password</DialogTitle>
                <DialogDescription>
                    Generate a temporary password for
                    <span class="font-semibold text-custom-accent-3">{{
                        user?.name ?? 'this user'
                    }}</span
                    > and email it to their registered address.
                </DialogDescription>
            </DialogHeader>
            <Separator />
            <DialogFooter class="pt-3 gap-2 sm:justify-end">
                <Button variant="ghost-outline" @click="open = false">
                    Cancel
                </Button>
                <Button variant="float-primary" @click="confirm">
                    <RiKey2Line class="h-4 w-4 shrink-0" />
                    Reset Password
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
