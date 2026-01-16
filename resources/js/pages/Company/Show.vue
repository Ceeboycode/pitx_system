<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { index, show } from '@/routes/companies'

/**
 * Props coming from the Laravel controller
 */
const props = defineProps<{
    company: {
        id: number
        company_name: string
        created_at_human?: string
        updated_at_human?: string
        creator?: {
            id: number
            name: string
        } | null
        updater?: {
            id: number
            name: string
        } | null
    }
}>()

/**
 * Breadcrumbs for the page
 * Example: Companies → Cavite Transport
 */
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Companies',
        href: index().url,
    },
    {
        title: props.company.company_name,
        href: show(props.company.id).url,
    },
]
</script>

<template>
    <!-- Browser tab title -->
    <Head :title="company.company_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">

            <!-- Page header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">
                    {{ company.company_name }}
                </h1>

                <button
                    class="rounded bg-gray-600 px-4 py-2 text-white hover:bg-gray-700"
                    @click="router.visit(index().url)"
                >
                    Back
                </button>
            </div>

            <!-- Company details -->
            <div class="rounded-lg border bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                    <div>
                        <p class="text-sm text-gray-500">Company Name</p>
                        <p class="font-medium">{{ company.company_name }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Created By</p>
                        <p class="font-medium">
                            {{ company.creator?.name ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Updated By</p>
                        <p class="font-medium">
                            {{ company.updater?.name ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Created At</p>
                        <p class="font-medium">
                            {{ company.created_at_human ?? '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Updated At</p>
                        <p class="font-medium">
                            {{ company.updated_at_human ?? '—' }}
                        </p>
                    </div>

                </div>
            </div>

        </div>
    </AppLayout>
</template>
