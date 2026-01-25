<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/route-stops';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

defineProps<{
    routeStops: {
        data: Array<{
            id: number;
            stop_name: string;
            route_name: string | null;
            order: number;
            created_at: string | null;
            deleted_at: string | null;
        }>;
        links: any[];
        meta: any;
    };
}>();


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Route Stops',
        href: index().url,
    },
];
</script>

<template>
    <Head title="Route Stops" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
<table class="w-full border rounded-lg">
    <thead class="bg-muted">
        <tr>
            <th class="px-4 py-2 text-left">Route</th>
            <th class="px-4 py-2 text-left">Stop Name</th>
            <th class="px-4 py-2 text-left">Order</th>
        </tr>
    </thead>
    <tbody>
        <tr
            v-for="stop in routeStops.data"
            :key="stop.id"
            class="border-t"
        >
            <td class="px-4 py-2">{{ stop.route_name }}</td>
            <td class="px-4 py-2">{{ stop.stop_name }}</td>
            <td class="px-4 py-2">{{ stop.order }}</td>
        </tr>
    </tbody>
</table>

<!-- Pagination -->
<div class="mt-4 flex flex-wrap gap-1">
    <a
        v-for="link in routeStops.links"
        :key="link.label"
        :href="link.url ?? '#'"
        v-html="link.label"
        class="px-3 py-1 rounded border text-sm"
        :class="{
            'bg-primary text-white': link.active,
            'opacity-50 pointer-events-none': !link.url,
        }"
    />
</div>


        </div>
    </AppLayout>
</template>
