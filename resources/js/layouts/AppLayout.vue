<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue'
import type { BreadcrumbItemType } from '@/types'
import { usePage } from '@inertiajs/vue3'
import { onMounted, watch } from 'vue'
import { toast } from 'vue-sonner'
import { Toaster } from '@/components/ui/sonner'

const page = usePage()

// TEMP: persistent sample toasts for styling. Remove this block, the onMounted import,
// and the `expand` / `visibleToasts` props on <Toaster> when done.
// onMounted(() => {
//     const persistent = { duration: Infinity, closeButton: true }

//     toast('Default toast', { ...persistent, description: 'This is a default toast with a description.' })
//     toast.success('Success toast', { ...persistent, description: 'Company was created successfully.' })
//     toast.info('Info toast', { ...persistent, description: 'Your session will expire in 5 minutes.' })
//     toast.warning('Warning toast', { ...persistent, description: 'This action cannot be undone.' })
//     toast.error('Error toast', { ...persistent, description: 'Something went wrong. Please try again.' })
//     toast.loading('Loading toast', { ...persistent, description: 'Saving your changes...' })
//     toast('Action toast', {
//         ...persistent,
//         description: 'Toast with action and cancel buttons.',
//         action: { label: 'Undo', onClick: () => {} },
//         cancel: { label: 'Dismiss', onClick: () => {} },
//     })
//     toast.success('No description', persistent)
// })

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
    <AppSidebarLayout>
        <Toaster position="bottom-right" expand :visibleToasts="20" />
        <slot />
    </AppSidebarLayout>
</template>
