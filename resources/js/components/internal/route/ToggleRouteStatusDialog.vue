<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { toggleStatus } from '@/actions/App/Http/Controllers/RouteController';

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
    <ConfirmDialog
        v-model:open="open"
        title="Set Route Status"
        :tone="route?.status === 'active' ? 'negative' : 'primary'"
        :confirm-label="route?.status === 'active' ? 'Inactivate' : 'Activate'"
        :icon="RiShutDownLine"
        :processing="processing"
        @confirm="confirm"
    >
        <template #description>
            Are you sure you want to set
            <span class="font-semibold text-custom-accent-3">{{ route?.route_name ?? 'this route' }}</span>
            to
            <span class="font-semibold text-custom-accent-3">
                {{ route?.status === 'active' ? 'inactive' : 'active' }}
            </span>?
        </template>
    </ConfirmDialog>
</template>
