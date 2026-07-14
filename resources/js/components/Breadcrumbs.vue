<script setup lang="ts">
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Link } from '@inertiajs/vue3';

interface BreadcrumbItemType {
    title: string;
    href?: string;
}

defineProps<{
    breadcrumbs: BreadcrumbItemType[];
}>();
</script>

<template>
    <Breadcrumb class="min-w-0">
        <BreadcrumbList class="ml-1 flex min-w-0 items-center gap-0 text-sm">
            <template v-for="(item, index) in breadcrumbs" :key="index">
                <BreadcrumbItem class="min-w-0">
                    <template v-if="index === breadcrumbs.length - 1">
                        <BreadcrumbPage class="truncate text-custom-shadow">
                            {{ item.title }}
                        </BreadcrumbPage>
                    </template>
                    <template v-else>
                        <BreadcrumbLink as-child class="text-custom-shadow/80 transition-colors hover:text-custom-shadow">
                            <Link :href="item.href ?? '#'" class="truncate">
                                {{ item.title }}
                            </Link>
                        </BreadcrumbLink>
                    </template>
                </BreadcrumbItem>
                <BreadcrumbSeparator
                    v-if="index !== breadcrumbs.length - 1"
                    class="text-custom-bg-dark dark:text-custom-shadow/20"
                >
                </BreadcrumbSeparator>
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>
