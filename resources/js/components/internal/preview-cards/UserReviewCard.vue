<script setup lang="ts">
import CardSeparator from '@/components/ui/_card-separator/CardSeparator.vue';
import { PreviewCard, ReviewCardRow } from '@/components/ui/_preview-card';

type UserValues = {
    name: string;
    email: string;
    phone_number: string;
    type: '' | 'internal' | 'external';
    role: string;
};

/**
 * Always-open review of the user being created, shown in the SidePanel of Users/Create.vue
 * so the values can be checked (and copied) while the form is filled in.
 */
const props = defineProps<{
    values: UserValues;
    company?: { company_name: string; company_code: string } | null;
}>();
</script>

<template>
    <PreviewCard
        title="Review"
        description="Review new user details before confirming."
        :closable="false"
    >
        <CardSeparator title="Account Info" />

        <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <ReviewCardRow label="Name" :value="props.values.name" />
            <ReviewCardRow label="Email" :value="props.values.email" />
            <ReviewCardRow label="Phone" :value="props.values.phone_number" />
        </div>

        <CardSeparator title="Access & Assignment Info" />

        <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <ReviewCardRow label="User Type" :value="props.values.type" value-class="capitalize" />
            <ReviewCardRow v-if="props.values.type" label="Role" :value="props.values.role" value-class="capitalize" />
        </div>

        <template v-if="props.values.type === 'external'">
            <CardSeparator title="Company Info" />

            <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                <ReviewCardRow label="Company" :value="props.company?.company_name" value-class="capitalize">
                    {{ props.company?.company_name || '—' }}
                    <span
                        v-if="props.company"
                        class="mr-1 cursor-pointer rounded-md bg-custom-bg px-2 font-mono font-normal tracking-widest group-hover:text-custom-shadow dark:bg-custom-bg-light"
                    >
                        {{ props.company.company_code }}
                    </span>
                </ReviewCardRow>
            </div>
        </template>

        <p class="mt-4 flex flex-col text-sm text-custom-shadow/80">
            <span>
                Default password is <span class="font-semibold">pitx@123</span>.
            </span>
            <span>
                New users are created with <span class="font-semibold">active</span> status.
            </span>
        </p>
    </PreviewCard>
</template>
