<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue'
import type { BreadcrumbItemType } from '@/types'
import { usePage } from '@inertiajs/vue3'
import { watch } from 'vue'
import { toast } from 'vue-sonner'
import { Toaster } from '@/components/ui/sonner'

const page = usePage()

watch(
    () => page.props.flash,
    (flash: any) => {
        if (flash?.success) toast.success(flash.success)
        if (flash?.error) toast.error(flash.error)
        if (flash?.info) toast.info(flash.info)
        if (flash?.warning) toast.warning(flash.warning)
    },
    { deep: true, immediate: true }
)
</script>

<template>
    <AppLayout>
        <Toaster position="top-right" />
        <slot />
    </AppLayout>
</template>
