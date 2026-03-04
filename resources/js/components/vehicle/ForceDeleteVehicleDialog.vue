<script setup lang="ts">
import { forceDelete } from '@/routes/vehicles';
import { router } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

const open = defineModel<boolean>('open');

const props = defineProps<{
    vehicle: {
        id: number;
        plate_number?: string | null;
        body_number?: string | null;
    };
}>();

const confirmation = ref('');
const processing = ref(false);

const canDelete = computed(() => confirmation.value.trim() === 'DELETE');

watch(open, (value) => {
    if (value) confirmation.value = '';
});

function deletePermanently() {
    if (!canDelete.value || processing.value) return;

    processing.value = true;

    router.delete(forceDelete({ vehicle: props.vehicle.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            confirmation.value = '';
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
        <DialogTrigger as-child>
            <Button variant="destructive" size="sm" class="cursor-pointer">
                <Trash2 class="mr-2 h-4 w-4" />
                Delete Permanently
            </Button>
        </DialogTrigger>

        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete Vehicle Permanently</DialogTitle>

                <DialogDescription class="space-y-3">
                    <p>
                        This action cannot be undone. It will permanently delete
                        <span class="font-medium">
                            {{
                                props.vehicle.plate_number ??
                                props.vehicle.body_number ??
                                `Vehicle #${props.vehicle.id}`
                            }}
                        </span>
                        and remove it from the system.
                    </p>

                    <p class="text-sm text-muted-foreground">
                        To confirm, please type
                        <span
                            class="mx-1 font-mono font-semibold text-destructive/80"
                        >
                            DELETE
                        </span>
                        below.
                    </p>

                    <Input
                        v-model="confirmation"
                        placeholder="Type DELETE to confirm"
                        class="mt-2"
                        :disabled="processing"
                        @keydown.enter.prevent="deletePermanently"
                    />
                </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <DialogClose as-child>
                    <Button
                        variant="outline"
                        :disabled="processing"
                        :class="
                            processing ? 'cursor-not-allowed' : 'cursor-pointer'
                        "
                    >
                        Cancel
                    </Button>
                </DialogClose>

                <Button
                    variant="destructive"
                    :disabled="!canDelete || processing"
                    @click="deletePermanently"
                    :class="
                        !canDelete || processing
                            ? 'cursor-not-allowed'
                            : 'cursor-pointer'
                    "
                >
                    <Trash2 class="mr-2 h-4 w-4" />
                    {{ processing ? 'Deleting...' : 'Delete Permanently' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
