<script setup lang="ts">
import { destroy } from '@/routes/gates';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

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

const open = defineModel<boolean>('open');

const props = defineProps<{
    gate: {
        id: number;
        gate_name: string;
    } | null;
}>();

const processing = ref(false);

function archive() {
    if (!props.gate) return;

    processing.value = true;

    router.delete(destroy(props.gate.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-w-md px-6">
            <DialogHeader class="px-0">
                <DialogTitle>Archive Gate</DialogTitle>
                <DialogDescription>
                    Are you sure you want to archive
                    <span class="font-semibold text-custom-accent-3">{{
                        gate?.gate_name || 'this gate'
                    }}</span>? You can restore it later from the Trash.
                </DialogDescription>
            </DialogHeader>
            <Separator />
            <DialogFooter class="pt-3 gap-2 sm:justify-end">
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
                    <RiArchive2Line class="h-4 w-4" />
                    {{ processing ? 'Archiving...' : 'Archive' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
