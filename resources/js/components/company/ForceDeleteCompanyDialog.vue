<script setup lang="ts">
import { forceDelete } from '@/routes/companies';
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
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

const open = defineModel<boolean>('open');

const props = defineProps<{
    company: {
        id: number;
        company_name: string;
    };
}>();

const confirmation = ref('');


const canDelete = computed(() => confirmation.value === 'DELETE');


watch(open, (value) => {
    if (value) confirmation.value = '';
});

function deletePermanently() {
    if (!canDelete.value) return;

    router.delete(forceDelete({ company: props.company.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            confirmation.value = '';
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete Company Permanently</DialogTitle>

                <DialogDescription class="space-y-3">
                    <p>
                        This action cannot be undone. It will permanently delete
                        <span class="font-medium">
                            {{ props.company.company_name }}
                        </span>
                        and remove it from the system.
                    </p>

                    <p class="text-sm text-muted-foreground">
                        To confirm, please type
                        <span
                            class="mx-1 font-mono font-semibold text-destructive/80"
                            >DELETE</span
                        >
                        below.
                    </p>

                    <Input
                        v-model="confirmation"
                        placeholder="Type DELETE to confirm"
                        class="mt-2"
                    />
                </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <DialogClose as-child>
                    <Button variant="outline">Cancel</Button>
                </DialogClose>

                <Button
                    variant="destructive"
                    :disabled="!canDelete"
                    @click="deletePermanently"
                >
                    <Trash2 class="mr-2 h-4 w-4" />
                    Delete Permanently
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
