<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { ConfirmDialog } from '@/components/ui/_app-dialog';
import { Textarea } from '@/components/ui/textarea';
import CompanyVehicleController from '@/actions/App/Http/Controllers/CompanyVehicleController';
import { RiShutDownLine } from 'vue-remix-icons';

type VehicleForToggle = {
    id: number;
    plate_number: string;
    status: string;
    operator_remark?: string | null;
};

const open = defineModel<boolean>('open');

const props = defineProps<{
    vehicle: VehicleForToggle | null;
}>();

const operatorRemark = ref('');
const processing = ref(false);

const isInactivating = computed(() => props.vehicle?.status === 'active');

watch(open, (value) => {
    if (value) {
        operatorRemark.value = props.vehicle?.operator_remark ?? '';
    }
});

function confirm() {
    if (!props.vehicle) return;
    if (isInactivating.value && !operatorRemark.value.trim()) return;

    processing.value = true;
    router.patch(
        CompanyVehicleController.toggleStatus(props.vehicle.id).url,
        {
            status: isInactivating.value ? 'inactive' : 'active',
            operator_remark: isInactivating.value ? operatorRemark.value : undefined,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
            onSuccess: () => {
                open.value = false;
            },
        },
    );
}
</script>

<template>
    <ConfirmDialog
        v-model:open="open"
        :title="isInactivating ? 'Inactivate Vehicle' : 'Activate Vehicle'"
        :tone="isInactivating ? 'negative' : 'primary'"
        :confirm-label="isInactivating ? 'Inactivate' : 'Activate'"
        processing-label="Updating..."
        size="lg"
        :icon="RiShutDownLine"
        :processing="processing"
        :confirm-disabled="isInactivating && !operatorRemark.trim()"
        @confirm="confirm"
    >
        <template #description>
            {{ isInactivating ? 'Provide a reason to inactivate' : 'This will activate' }}
            <span class="font-semibold text-custom-accent-3">{{ vehicle?.plate_number || 'this vehicle' }}</span>.
            {{ isInactivating ? 'You can activate it again later.' : 'All documents must be approved before activation.' }}
        </template>

        <Textarea
            v-if="isInactivating"
            v-model="operatorRemark"
            rows="3"
            placeholder="Brief reason for inactivation..."
            class="resize-none"
        />
    </ConfirmDialog>
</template>
