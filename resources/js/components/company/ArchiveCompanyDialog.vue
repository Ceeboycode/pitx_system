<script setup lang="ts">
import { destroy } from '@/routes/companies';
import { router } from '@inertiajs/vue3';
import { ArchiveX } from 'lucide-vue-next';
import { ref } from 'vue';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

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
    <AlertDialog v-model:open="open">
        <AlertDialogContent class="rounded-lg p-4">
            <AlertDialogHeader>
                <AlertDialogTitle>Archive Company</AlertDialogTitle>

                <AlertDialogDescription class="">
                    <span>
                        Are you sure you want to archive
                        <span class="font-bold">{{
                            props.company.company_name
                        }}</span
                        >?
                    </span>

                    <span class="text-muted-foreground">
                        You can restore it later from Trash.
                    </span>
                </AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <AlertDialogCancel
                    variant="outline"
                    class="cursor-pointer hover:bg-slate-100 rounded-lg"
                    :disabled="processing"
                >
                    Cancel
                </AlertDialogCancel>

                <AlertDialogAction
                    :disabled="processing"
                    @click="archive"
                    class="cursor-pointer bg-destructive text-destructive-foreground hover:bg-destructive/70 rounded-lg"
                >
                    <ArchiveX class="h-4 w-4" />
                    {{ processing ? 'Archiving...' : 'Yes, Archive Company' }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
