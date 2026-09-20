<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { update } from '@/routes/companies';

import { RiShutDownLine } from 'vue-remix-icons';

type CompanyForToggle = {
    id: number;
    company_name: string;
    is_active?: boolean | number | null;
};

const open = defineModel<boolean>('open');

const props = defineProps<{
    company: CompanyForToggle | null;
}>();

const processing = ref(false);

function confirm() {
    if (!props.company) return;

    processing.value = true;
    router.put(
        update(props.company.id).url,
        { is_active: !props.company.is_active },
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
        title="Set Company Status"
        :tone="company?.is_active ? 'negative' : 'primary'"
        :confirm-label="company?.is_active ? 'Inactivate' : 'Activate'"
        :icon="RiShutDownLine"
        :processing="processing"
        @confirm="confirm"
    >
        <template #description>
            Are you sure you want to set
            <span class="font-semibold text-custom-accent-3">{{ company?.company_name ?? 'this company' }}</span>
            as {{ company?.is_active ? 'inactive' : 'active' }}?
        </template>
    </ConfirmDialog>
</template>
