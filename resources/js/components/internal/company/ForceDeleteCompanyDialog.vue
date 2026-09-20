<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { forceDelete } from '@/routes/companies';
import { router } from '@inertiajs/vue3';
import { RiDeleteBin7Line } from 'vue-remix-icons';
import { ref } from 'vue';

const open = defineModel<boolean>('open');

const props = defineProps<{
    company: {
        id: number;
        company_name: string;
    };
}>();

const processing = ref(false);

function deletePermanently() {
    processing.value = true;

    router.delete(forceDelete({ company: props.company.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        title="Delete Company Permanently"
        tone="negative"
        confirm-label="Delete Permanently"
        processing-label="Deleting..."
        confirm-text="DELETE"
        :icon="RiDeleteBin7Line"
        :processing="processing"
        @confirm="deletePermanently"
    >
        <template #description>
            This action cannot be undone. It will permanently delete
            <span class="font-semibold text-custom-accent-3">{{ props.company.company_name }}</span>
            and remove it from the system.
        </template>
    </ConfirmDialog>
</template>
