<script setup lang="ts">
import { destroy } from '@/routes/users';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

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
import { ArchiveX } from 'lucide-vue-next';

const open = defineModel<boolean>('open');

const props = defineProps<{
    user: {
        id: number;
        name: string;
    };
}>();

const processing = ref(false);

function archive() {
    processing.value = true;

    router.delete(destroy(props.user.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            toast.success('User archived successfully.');
        },
        onError: () => {
            toast.error('Failed to archive user.');
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="px-6">
            <DialogHeader class="px-0">
                <DialogTitle>Archive User</DialogTitle>
                <DialogDescription class="mt-4">
                    Are you sure you want to archive
                    <span class="font-semibold text-custom-accent-3">{{
                        props.user.name
                    }}</span>? You can restore this account from Archived Users.
                </DialogDescription>
            </DialogHeader>
            <Separator class="mb-4" />
            <DialogFooter class="gap-2 sm:justify-end">
                <Button
                    variant="ghost-outline"
                    :disabled="processing"
                    @click="open = false"
                >
                    Cancel
                </Button>
                <Button
                    variant="destructive"
                    :disabled="processing"
                    @click="archive"
                >
                    <ArchiveX class="h-4 w-4" />
                    {{ processing ? 'Archiving...' : 'Archive' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
