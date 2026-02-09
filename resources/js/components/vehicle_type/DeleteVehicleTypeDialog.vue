<script setup lang="ts">
import { destroy } from '@/routes/vehicle-types';
import { router } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';

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
    vehicle_type: {
        id: number;
        type_name: string;
    };
}>();

function deletePermanently() {
    router.delete(destroy({ vehicle_type: props.vehicle_type.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <AlertDialog v-model:open="open">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle> Delete Vehicle Type </AlertDialogTitle>

                <AlertDialogDescription>
                    Are you sure you want to delete
                    <span class="font-medium">
                        {{ props.vehicle_type.type_name }}
                    </span>
                    <!-- ? You can restore it later from Trash. -->
                </AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <AlertDialogCancel> Cancel </AlertDialogCancel>

                <AlertDialogAction as-child>
                    <Button variant="default" @click="deletePermanently">
                        <Trash2 class="h-4 w-4" />
                        Delete
                    </Button>
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
