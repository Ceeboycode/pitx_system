<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const isOpen = usePage().props.sidebarOpen;
</script>

<template>
    <SidebarProvider :default-open="isOpen">
        <AppSidebar />
        <div
            data-slot="sidebar-inset"
            class="relative flex h-screen min-h-0 w-full flex-1 flex-col overflow-x-hidden bg-custom-bg-light dark:bg-custom-bg lg:pb-6 lg:pr-6"
        >
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <main class="flex min-h-0 flex-1 flex-col bg-custom-bg p-6 shadow-sm inset-shadow-sm dark:bg-custom-bg-dark dark:shadow-white/5 dark:inset-shadow-none lg:rounded-3xl">
                <div class="mx-auto flex min-h-0 w-full flex-1 flex-col">
                    <slot />
                </div>
            </main>
        </div>
    </SidebarProvider>
</template>
