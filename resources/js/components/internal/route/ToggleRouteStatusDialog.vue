<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { toggleStatus } from '@/actions/App/Http/Controllers/RouteController';

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

type RouteForToggle = {
    id: number;
    route_name: string;
    status: 'active' | 'inactive' | null;
};

const open = defineModel<boolean>('open');

const props = defineProps<{
    route: RouteForToggle | null;
}>();

const processing = ref(false);

function confirm() {
    if (!props.route) return;

    processing.value = true;
    router.patch(
        toggleStatus(props.route.id).url,
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
                <DialogTitle>Set route status</DialogTitle>
                <DialogDescription>
                    Are you sure you want to set
                    <span class="font-semibold text-custom-accent-3">{{ route?.route_name ?? 'this route' }}</span>
                    to
                    <span class="font-semibold text-custom-accent-3">
                        {{ route?.status === 'active' ? 'inactive' : 'active' }}
                    </span>?
                </DialogDescription>
            </DialogHeader>
            <Separator />
            <DialogFooter class="pt-3 gap-2 sm:justify-end">
                <Button variant="ghost-outline" :disabled="processing" @click="open = false">
                    Cancel
                </Button>
                <Button
                    :variant="route?.status === 'active' ? 'destructive' : 'float-primary'"
                    :disabled="processing"
                    @click="confirm"
                >
                    <RiShutDownLine class="h-4 w-4" />
                    {{ route?.status === 'active' ? 'Inactivate' : 'Activate' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
