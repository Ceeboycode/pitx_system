<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { restore as restoreVehicle } from '@/routes/vehicles';
import { router } from '@inertiajs/vue3';
import { RotateCcw } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    vehicle: {
        id: number;
        plate_number?: string | null;
        body_number?: string | null;
    };
}>();

const processing = ref(false);

function restore() {
    processing.value = true;

    router.post(
        restoreVehicle(props.vehicle.id).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => (processing.value = false),
        },
    );
}
</script>

<template>
    <AlertDialog>
        <!-- <AlertDialogTrigger as-child>
            <Button variant="outline" size="sm" class="cursor-pointer">
                <RotateCcw class="mr-2 h-4 w-4" />
                Restore
            </Button>
        </AlertDialogTrigger> -->

        <AlertDialogContent class="rounded-lg p-4">
            <AlertDialogHeader>
                <AlertDialogTitle>Restore Vehicle</AlertDialogTitle>

                <AlertDialogDescription>
                    <span>You are about to restore this vehicle:</span>

                    <div class="rounded-md bg-muted p-3 text-sm">
                        <div>
                            <span class="font-medium">Plate:</span>
                            {{ props.vehicle.plate_number ?? '—' }}
                        </div>
                        <div>
                            <span class="font-medium">Body:</span>
                            {{ props.vehicle.body_number ?? '—' }}
                        </div>
                    </div>

                    <span class="text-muted-foreground">
                        This vehicle will be moved back to the active list.
                    </span>
                </AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <AlertDialogCancel
                    class="rounded-lg cursor-pointer hover:bg-slate-100"
                    :disabled="processing"
                >
                    Cancel
                </AlertDialogCancel>

                <AlertDialogAction
                    :disabled="processing"
                    @click="restore"
                    class="rounded-lg border-0 text-white cursor-pointer bg-primary hover:bg-primary/90"
                >
                    <RotateCcw class="h-4 w-4" />
                    {{ processing ? 'Restoring...' : 'Yes, Restore Vehicle' }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
