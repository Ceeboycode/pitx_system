<script setup lang="ts">
import { destroy } from '@/routes/gates';
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
    gate: {
        id: number;
        gate_name: string;
    };
}>();

const processing = ref(false);

function archive() {
    processing.value = true;

    router.delete(destroy(props.gate.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            toast.success('Gate archived successfully.');
        },
        onError: () => {
            toast.error('Failed to archive gate.');
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
                <DialogTitle>Archive Gate</DialogTitle>
                <DialogDescription class="mt-4">
                    Are you sure you want to archive
                    <span class="font-semibold text-custom-accent-3">{{
                        props.gate.gate_name
                    }}</span>? You can restore it later from the Trash.
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
