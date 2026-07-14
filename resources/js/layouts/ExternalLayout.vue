<script setup lang="ts">
import ExternalSidebar from '@/components/ExternalSidebar.vue';
import MessagingPanel from '@/components/MessagingPanel.vue';
import NotificationDropdown from '@/components/NotificationDropdown.vue';
import { SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { Toaster } from '@/components/ui/sonner';
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { toast } from 'vue-sonner';

type FlashProps = {
    success?: string | null;
    error?: string | null;
    info?: string | null;
    warning?: string | null;
};

type PageProps = {
    sidebarOpen: boolean;
    flash?: FlashProps;
};

const page = usePage<PageProps>();
const isOpen = page.props.sidebarOpen;

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;
        if (flash.success) toast.success(flash.success);
        if (flash.error) toast.error(flash.error);
        if (flash.info) toast.info(flash.info);
        if (flash.warning) toast.warning(flash.warning);
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <SidebarProvider :default-open="isOpen">
        <ExternalSidebar />

        <div
            data-slot="sidebar-inset"
            class="relative flex h-screen min-h-0 w-full flex-1 flex-col overflow-x-hidden bg-custom-bg-light dark:bg-custom-bg lg:pb-6 lg:pr-6"
        >
            <header class="sticky top-0 z-30 shrink-0 bg-custom-bg-light dark:bg-custom-bg">
                <div class="flex items-center justify-between gap-2 px-6 pb-3 pt-6 lg:px-0">
                    <SidebarTrigger class="h-9 w-9 rounded-full bg-custom-bg text-custom-shadow hover:bg-custom-secondary/20 dark:bg-custom-bg-light dark:hover:bg-custom-secondary/20" />

                    <div class="flex items-center gap-2 sm:gap-3">
                        <MessagingPanel />
                        <NotificationDropdown />
                    </div>
                </div>
            </header>

            <main class="flex min-h-0 flex-1 flex-col bg-custom-bg p-6 shadow-sm inset-shadow-sm dark:bg-custom-bg-dark dark:shadow-white/5 dark:inset-shadow-none lg:rounded-3xl">
                <div class="mx-auto flex min-h-0 w-full flex-1 flex-col">
                    <slot />
                </div>
            </main>

            <Toaster position="top-right" />
        </div>
    </SidebarProvider>
</template>
