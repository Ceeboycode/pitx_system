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
        <AlertDialogContent>
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
                <AlertDialogCancel>Cancel</AlertDialogCancel>

                <AlertDialogAction as-child>
                    <Button variant="secondary" @click="restoreCompany">
                        <RotateCcw class="mr-2 h-4 w-4" />
                        Restore
                    </Button>
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
