<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

import DispatchController from '@/actions/App/Http/Controllers/DispatchController';
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { InputMessage } from '@/components/ui/_input-message';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RiLogoutBoxLine } from 'vue-remix-icons';

type DispatchForDeparture = {
    id: number;
    plate_number: string;
    pax_count?: number | null;
};

const open = defineModel<boolean>('open');

const props = defineProps<{
    dispatch: DispatchForDeparture | null;
}>();

const form = useForm({ pax_count: '' });

watch(open, (isOpen) => {
    if (!isOpen) return;

    form.reset();
    form.clearErrors();
    form.pax_count = props.dispatch?.pax_count ? String(props.dispatch.pax_count) : '';
});

function confirm() {
    if (!props.dispatch || form.processing) return;

    form.patch(DispatchController.depart(props.dispatch.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        title="Mark as Departed"
        confirm-label="Confirm Departure"
        processing-label="Saving..."
        :icon="RiLogoutBoxLine"
        :processing="form.processing"
        @confirm="confirm"
    >
        <template #description>
            This will record the departure of
            <span class="font-semibold text-custom-accent-3">{{ dispatch?.plate_number ?? 'this dispatch' }}</span>
            as now. Departed dispatches can no longer be edited.
        </template>

        <div class="space-y-1">
            <Label for="depart_pax_count" class="flex items-center gap-1">
                Passenger Count <span class="text-destructive">*</span>
            </Label>
            <Input
                id="depart_pax_count"
                v-model="form.pax_count"
                type="number"
                min="15"
                placeholder="e.g. 15"
                @keydown.enter.prevent="confirm"
            />
            <InputMessage variant="destructive" :message="form.errors.pax_count" />
        </div>
    </ConfirmDialog>
</template>
