<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index, update } from '@/routes/vehicle-types';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import { LeadPanel } from '@/components/ui/_panels';
import { LeadingCard } from '@/components/ui/_leading-card';
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/_tabs';
import { RiDashboardHorizontalLine, RiLoader2Line } from 'vue-remix-icons';
import { can } from '@/lib/can';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';

type VehicleType = {
    id: number;
    type_name: string;
    is_active: boolean;
    created_at_human: string | null;
    updated_at_human: string | null;
    creator: { name: string } | null;
    updater: { name: string } | null;
};

const props = defineProps<{ vehicleType: VehicleType }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vehicle Types', href: index().url },
    { title: props.vehicleType.type_name, href: '#' },
];

const tabs = [
    {
        value: 'overview',
        label: 'Overview',
        icon: RiDashboardHorizontalLine,
    },
];

const form = useForm({
    type_name: props.vehicleType.type_name,
    is_active: props.vehicleType.is_active ? 1 : 0,
});

function submit() {
    form.put(update({ vehicle_type: props.vehicleType.id }).url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Edit ${vehicleType.type_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <LeadPanel>
            <template #leading>
                <LeadingCard
                    :title="vehicleType.type_name"
                    subtitle="Vehicle Type"
                    :status="vehicleType.is_active ? 'active' : 'inactive'"
                    :attributes="[
                        { label: 'Created By', value: vehicleType.creator?.name ?? '—' },
                        { label: 'Created At', value: vehicleType.created_at_human ?? '—' },
                        { label: 'Updated By', value: vehicleType.updater?.name ?? '—' },
                        { label: 'Updated At', value: vehicleType.updated_at_human ?? '—' },
                    ]"
                >
                </LeadingCard>
            </template>

            <Tabs default-value="overview" class="flex min-h-0 flex-1 flex-col">
                <div class="px-6 pt-4 shrink-0 overflow-x-auto no-scrollbar border-b border-custom-bg-dark dark:border-custom-bg-light">
                    <TabsList class="inline-flex h-auto w-auto justify-start gap-6 bg-transparent p-0 pb-3">
                        <TabsTrigger
                            v-for="tab in tabs"
                            :key="tab.value"
                            :value="tab.value"
                            class="inline-flex items-center gap-2 rounded-none border-b-2 border-transparent p-0 text-sm font-medium text-custom-shadow/60 hover:text-custom-shadow data-[state=active]:border-custom-primary data-[state=active]:text-custom-shadow data-[state=active]:shadow-none data-[state=active]:bg-transparent"
                        >
                            <component :is="tab.icon" class="h-4 w-4" />
                            {{ tab.label }}
                        </TabsTrigger>
                    </TabsList>
                </div>
                
                <div class="flex-1 overflow-y-auto p-6 bg-custom-bg dark:bg-custom-bg-dark">
                    <TabsContent value="overview" class="m-0 border-0 p-0 h-full">
                        <Card class="shadow-none border-none max-w-2xl bg-transparent">
                            <CardHeader class="px-0">
                                <CardTitle>Edit Vehicle Type Details</CardTitle>
                                <CardDescription>
                                    Update the information for this vehicle type.
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="px-0">
                                <form @submit.prevent="submit" class="space-y-4">
                                    <div class="grid gap-2">
                                        <Label for="type_name">Type Name <span class="text-rose-500">*</span></Label>
                                        <Input
                                            id="type_name"
                                            v-model="form.type_name"
                                            placeholder="e.g. Mini Bus"
                                            required
                                            class="bg-custom-bg-light dark:bg-custom-bg"
                                        />
                                        <InputError :message="form.errors.type_name" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="is_active">Status <span class="text-rose-500">*</span></Label>
                                        <Select v-model="form.is_active" required>
                                            <SelectTrigger id="is_active" class="bg-custom-bg-light dark:bg-custom-bg w-full">
                                                <SelectValue placeholder="Select status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem :value="1">Active</SelectItem>
                                                <SelectItem :value="0">Inactive</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError :message="form.errors.is_active" />
                                    </div>
                                    
                                    <div class="flex pt-4">
                                        <Button
                                            type="submit"
                                            variant="float-primary"
                                            :disabled="form.processing"
                                        >
                                            <RiLoader2Line v-if="form.processing" class="mr-2 h-4 w-4 shrink-0 animate-spin" />
                                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </div>
            </Tabs>
        </LeadPanel>
    </AppLayout>
</template>
