<script setup lang="ts">
import { computed } from 'vue';
import { CardSeparator } from '@/components/ui/_card-separator';
import { PreviewCard, ReviewCardRow } from '@/components/ui/_preview-card';

type ReviewValues = {
    vehicle_type_id: number | string | null;
    plate_number: string;
    body_number: string;
    capacity: string | number;
    color: string;
    make_model: string;
    documents: Array<{ file: File | null }>;
};

/**
 * Always-open review of the vehicle being registered, shown in the SidePanel of
 * External/Vehicles/Create.vue so the values can be checked while the form is filled in.
 */
const props = defineProps<{
    values: ReviewValues;
    vehicleTypeName?: string | null;
    selectedRouteName?: string | null;
    requiredDocumentsCount: number;
    userName: string;
}>();

const uploadedDocumentsCount = computed(
    () => props.values.documents.filter((doc) => doc.file !== null).length,
);
</script>

<template>
    <PreviewCard
        title="Review"
        description="Review the vehicle details before submitting."
        :closable="false"
    >
        <CardSeparator title="Vehicle Info" />

        <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <ReviewCardRow label="Vehicle Type" :value="props.vehicleTypeName" />
            <ReviewCardRow label="Plate Number" :value="props.values.plate_number" />
            <ReviewCardRow label="Body Number" :value="props.values.body_number" />
            <ReviewCardRow label="Capacity" :value="String(props.values.capacity || '')" />
            <ReviewCardRow label="Color" :value="props.values.color" />
            <ReviewCardRow label="Make / Model" :value="props.values.make_model" />
        </div>

        <CardSeparator title="Route & Documents" />

        <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <ReviewCardRow label="Assigned Route" :value="props.selectedRouteName" />
            <ReviewCardRow
                label="Documents"
                :value="`${uploadedDocumentsCount} of ${props.requiredDocumentsCount} attached`"
            />
            <ReviewCardRow label="Submitted By" :value="props.userName" />
        </div>

        <p class="mt-4 flex flex-col text-sm text-custom-shadow/80">
            <span>
                New vehicles are submitted for <span class="font-semibold">verification</span> before they can operate.
            </span>
        </p>
    </PreviewCard>
</template>
