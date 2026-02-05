<script setup lang="ts">
import { destroy } from '@/routes/companies';
import { router } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const open = defineModel<boolean>('open');

const props = defineProps<{
    company: {
        id: number;
        company_name: string;
    };
}>();

function archive() {
    router.delete(destroy({ company: props.company.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Archive Company</DialogTitle>
                <DialogDescription>
                    Are you sure you want to archive
                    <span class="font-medium">{{
                        props.company.company_name
                    }}</span
                    >? You can restore it from Trash.
                </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <Button variant="outline" type="button" @click="open = false">
                    Cancel
                </Button>

                <Button variant="archive" type="button" @click="archive">
                    <Save class="mr-2 h-4 w-4" />
                    Archive
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
