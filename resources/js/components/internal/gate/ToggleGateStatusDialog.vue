<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { toggleStatus } from '@/routes/gates';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import Separator from '@/components/ui/separator/Separator.vue';
import { RiShutDownLine } from 'vue-remix-icons';

type GateForToggle = {
    id: number;
    gate_name: string;
    status: 'active' | 'inactive';
};

const open = defineModel<boolean>('open');

const props = defineProps<{
    gate: GateForToggle | null;
}>();

const processing = ref(false);

function confirm() {
    if (!props.gate) return;

    processing.value = true;
    router.patch(
        toggleStatus(props.gate.id).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
                open.value = false;
            },
        },
    );
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-w-md px-6">
            <DialogHeader class="px-0">
                <DialogTitle>Set gate status</DialogTitle>
                <DialogDescription>
                    Are you sure you want to set
                    <span class="font-semibold text-custom-accent-3">{{ gate?.gate_name ?? 'this gate' }}</span>
                    as {{ gate?.status === 'active' ? 'inactive' : 'active' }}?
                </DialogDescription>
            </DialogHeader>
            <Separator />
            <DialogFooter class="pt-3 gap-2 sm:justify-end">
                <Button variant="ghost-outline" :disabled="processing" @click="open = false">Cancel</Button>
                <Button
                    :variant="gate?.status === 'active' ? 'destructive' : 'float-primary'"
                    :disabled="processing"
                    @click="confirm"
                >
                    <RiShutDownLine class="h-4 w-4" />
                    {{ gate?.status === 'active' ? 'Inactivate' : 'Activate' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
