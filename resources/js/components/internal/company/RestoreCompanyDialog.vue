<script setup lang="ts">
import { restore } from '@/routes/companies';
import { router } from '@inertiajs/vue3';
import { RiRestartLine } from 'vue-remix-icons';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { Button } from '@/components/ui/button';

const open = defineModel<boolean>('open');


const props = defineProps<{
    company: {
        id: number;
        company_name: string;
    };
}>();


function restoreCompany() {
    router.patch(
        restore({ company: props.company.id }).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
            },
        }
    );
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-w-md px-6" :show-close-button="false">
            <DialogHeader class="px-0">
                <DialogTitle>Restore</DialogTitle>
                <DialogDescription>
                    Are you sure you want to restore
                    <span class="font-semibold text-custom-accent-3">{{
                        props.company.company_name
                    }}</span>? This company will become active again.
                </DialogDescription>
            </DialogHeader>
            <Separator />
            <DialogFooter class="pt-3 gap-2 sm:justify-end">
                <Button variant="ghost-outline" @click="open = false">
                    Cancel
                </Button>
                <Button variant="float-primary" @click="restoreCompany">
                    <RiRestartLine class="h-4 w-4" />
                    Restore
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
