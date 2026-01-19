<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    vehicleType: {
        id: number;
        type_name: string;
        is_active: boolean;
        created_at_human: string | null;
        updated_at_human: string | null;
        creator: {
            id: number;
            name: string;
        } | null;
        updater: {
            id: number;
            name: string;
        } | null;
    };
}>();

import { index, show } from '@/routes/vehicle-types';
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Vehicle Types Table',
        href: index().url,
    },
    {
        title: 'Vehicle Type Details',
        href: show(props.vehicleType.id).url,
    },
];
</script>

<template>
    <Head :title="`Vehicle Type - ${vehicleType.type_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex max-w-4xl flex-col gap-6 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">
                    Vehicle Type Details
                </h1>
            </div>

            <div class="rounded-lg border bg-white shadow-sm">
                <div class="divide-y">
                    <!-- Vehicle Type -->
                    <div class="flex justify-between px-6 py-4">
                        <span class="text-sm font-medium text-gray-500">
                            Vehicle Type
                        </span>
                        <span class="text-sm text-gray-900">
                            {{ vehicleType.type_name }}
                        </span>
                    </div>

                    <!-- Status -->
                    <div class="flex justify-between px-6 py-4">
                        <span class="text-sm font-medium text-gray-500">
                            Status
                        </span>
                        <span
                            class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                            :class="
                                vehicleType.is_active
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700'
                            "
                        >
                            {{ vehicleType.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <!-- Created By -->
                    <div class="flex justify-between px-6 py-4">
                        <span class="text-sm font-medium text-gray-500">
                            Created By
                        </span>
                        <span class="text-sm text-gray-900">
                            {{ vehicleType.creator?.name ?? '—' }}
                        </span>
                    </div>

                    <!-- Created At -->
                    <div class="flex justify-between px-6 py-4">
                        <span class="text-sm font-medium text-gray-500">
                            Created
                        </span>
                        <span class="text-sm text-gray-900">
                            {{ vehicleType.created_at_human ?? '—' }}
                        </span>
                    </div>

                    <!-- Updated By -->
                    <div class="flex justify-between px-6 py-4">
                        <span class="text-sm font-medium text-gray-500">
                            Last Updated By
                        </span>
                        <span class="text-sm text-gray-900">
                            {{ vehicleType.updater?.name ?? '—' }}
                        </span>
                    </div>

                    <!-- Updated At -->
                    <div class="flex justify-between px-6 py-4">
                        <span class="text-sm font-medium text-gray-500">
                            Last Updated
                        </span>
                        <span class="text-sm text-gray-900">
                            {{ vehicleType.updated_at_human ?? '—' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
                <Link
                    :href="index().url"
                    class="rounded bg-gray-600 px-4 py-2 text-sm text-white hover:bg-gray-700"
                >
                    Back to List
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
