<script setup lang="ts">
import { restore } from '@/routes/companies';
import { router } from '@inertiajs/vue3';
import { RotateCcw } from 'lucide-vue-next';

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
    <AlertDialog v-model:open="open">
        <AlertDialogContent class="rounded-lg p-4">
            <AlertDialogHeader>
                <AlertDialogTitle>Restore Company</AlertDialogTitle>

                <AlertDialogDescription>
                    Are you sure you want to restore
                    <span class="font-medium">
                        {{ props.company.company_name }}
                    </span>
                    ?
                    <br />
                    This company will become active again.
                </AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <AlertDialogCancel class="rounded-lg cursor-pointer hover:bg-slate-100">Cancel</AlertDialogCancel>

                <AlertDialogAction as-child class="rounded-lg border-0 cursor-pointer bg-primary hover:bg-primary/90 text-primary-foreground hover:text-primary-foreground">
                    <Button variant="outline" @click="restoreCompany">
                        <RotateCcw class="h-4 w-4" />
                        Restore
                    </Button>
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
