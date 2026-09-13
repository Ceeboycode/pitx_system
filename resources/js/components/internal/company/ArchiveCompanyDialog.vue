<script setup lang="ts">
import { destroy } from '@/routes/companies';
import { router } from '@inertiajs/vue3';
import { ArchiveX } from 'lucide-vue-next';
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

const open = defineModel<boolean>('open');

const props = defineProps<{
    company: {
        id: number;
        company_name: string;
    };
}>();

const processing = ref(false);

function archive() {
    processing.value = true;

    router.delete(destroy({ company: props.company.id }).url, {
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
        <DialogContent class="px-6">
            <DialogHeader class="px-0">
                <DialogTitle>Archive Company</DialogTitle>
                <DialogDescription class="mt-4">
                    Are you sure you want to archive
                    <span class="font-semibold text-custom-accent-3">{{
                        props.company.company_name
                    }}</span
                    >? This action will remove it from active records.
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
