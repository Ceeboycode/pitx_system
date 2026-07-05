<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import MessagingPanel from '@/components/MessagingPanel.vue';
import NotificationDropdown from '@/components/NotificationDropdown.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="sticky top-0 z-30 shrink-0 bg-custom-bg-light dark:bg-custom-bg"
    >
        <div class="flex items-center justify-between gap-2 px-6 pb-3 pt-6 lg:px-0">
            <div class="flex min-w-0 items-center gap-2">
                <SidebarTrigger class="h-9 w-9 rounded-full bg-custom-bg text-custom-shadow hover:bg-custom-secondary/20 dark:bg-custom-bg-light dark:hover:bg-custom-secondary/20" />
                <template v-if="breadcrumbs && breadcrumbs.length > 0">
                    <Breadcrumbs :breadcrumbs="breadcrumbs" />
                </template>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <MessagingPanel />
                <NotificationDropdown />
            </div>
        </div>
    </header>
</template>
