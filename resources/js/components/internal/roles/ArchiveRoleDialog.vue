<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { destroy } from '@/routes/roles';

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
import { RiArchive2Line } from 'vue-remix-icons';

type RoleForArchive = {
    id: number;
    name: string;
};

const open = defineModel<boolean>('open');

const props = defineProps<{
    role: RoleForArchive | null;
}>();

const processing = ref(false);

function archive() {
    if (processing.value || !props.role) return;

    processing.value = true;
    router.delete(destroy({ role: props.role.id }).url, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-w-md px-6" :show-close-button="false">
            <DialogHeader class="px-0">
                <DialogTitle>Archive role</DialogTitle>
                <DialogDescription>
                    Are you sure you want to archive
                    <span class="font-semibold text-custom-accent-3">{{ role?.name }}</span>?
                    It can be restored later.
                </DialogDescription>
            </DialogHeader>
            <Separator />
            <DialogFooter class="pt-3 gap-2 sm:justify-end">
                <Button variant="ghost-outline" @click="open = false">Cancel</Button>
                <Button
                    variant="destructive"
                    :disabled="processing"
                    @click="archive"
                >
                    <RiArchive2Line class="h-4 w-4 shrink-0" />
                    {{ processing ? 'Archiving...' : 'Archive' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
