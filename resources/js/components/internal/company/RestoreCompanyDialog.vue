<script setup lang="ts">
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { restore } from '@/routes/companies';
import { router } from '@inertiajs/vue3';
import { RiRestartLine } from 'vue-remix-icons';


const open = defineModel<boolean>('open');


const props = defineProps<{
    company: {
        id: number;
        company_name: string;
    };
}>();


function restoreCompany() {
    router.patch(
        restore({ company: props.company.id }).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
            },
        }
    );
}
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        title="Restore Company"
        tone="primary"
        confirm-label="Restore"
        :icon="RiRestartLine"
        @confirm="restoreCompany"
    >
        <template #description>
            Are you sure you want to restore
            <span class="font-semibold text-custom-accent-3">{{
                props.company.company_name
            }}</span>? This company will become active again.
        </template>
    </ConfirmDialog>
</template>
