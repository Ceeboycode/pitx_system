<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { update } from '@/routes/companies';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
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
    <Dialog v-model:open="open">
        <DialogContent class="max-w-md px-6" :show-close-button="false">
            <DialogHeader class="px-0">
                <DialogTitle>Set company status</DialogTitle>
                <DialogDescription>
                    Are you sure you want to set
                    <span class="font-semibold text-custom-accent-3">{{ company?.company_name ?? 'this company' }}</span>
                    as {{ company?.is_active ? 'inactive' : 'active' }}?
                </DialogDescription>
            </DialogHeader>
            <Separator />
            <DialogFooter class="pt-3 gap-2 sm:justify-end">
                <Button variant="ghost-outline" :disabled="processing" @click="open = false">Cancel</Button>
                <Button
                    :variant="company?.is_active ? 'destructive' : 'float-primary'"
                    :disabled="processing"
                    @click="confirm"
                >
                    <RiShutDownLine class="h-4 w-4 shrink-0" />
                    {{ company?.is_active ? 'Inactivate' : 'Activate' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
