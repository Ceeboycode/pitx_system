<script setup lang="ts">
import { Button } from '@/components/ui/button';
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
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';

import { destroy } from '@/routes/vehicles';

type Vehicle = {
    id: number;
    plate_number?: string | null;
    body_number?: string | null;
};

const props = defineProps<{
    vehicle: Vehicle;
}>();

const processing = ref(false);

function archive() {
    if (processing.value) return;
    processing.value = true;

    router.delete(destroy({ vehicle: props.vehicle.id }).url, {
        preserveScroll: true,

        onFinish: () => (processing.value = false),
    });
}
</script>

<template>
    <AlertDialog>
        <AlertDialogTrigger as-child>
            <Button variant="archive" size="sm" class="cursor-pointer">
                <ArchiveX class="mr-2 h-4 w-4" />
                Archive
            </Button>
        </AlertDialogTrigger>

        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle> Archive Vehicle </AlertDialogTitle>

                <AlertDialogDescription class="space-y-2">
                    <span> You are about to archive this vehicle: </span>

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
                        Archived vehicles can be restored later from the
                        Archived page.
                    </span>
                </AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <AlertDialogCancel class="cursor-pointer">
                    Cancel
                </AlertDialogCancel>

                <AlertDialogAction
                    :disabled="processing"
                    @click="archive"
                    class="cursor-pointer"
                >
                    <ArchiveX class="mr-2 h-4 w-4" />
                    {{ processing ? 'Archiving...' : 'Yes, Archive Vehicle' }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
