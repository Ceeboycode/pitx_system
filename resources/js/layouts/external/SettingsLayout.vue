<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useActiveUrl } from '@/composables/useActiveUrl';
import { Link } from '@inertiajs/vue3';

const sidebarNavItems: Array<{ title: string; href: string }> = [
    {
        title: 'Personal Profile',
        href: '/company/settings/profile',
    },
    {
        title: 'Password',
        href: '/company/settings/password',
    },
    {
        title: 'Appearance',
        href: '/company/settings/appearance',
    },
];

const { urlIsActive } = useActiveUrl();
</script>

<template>
    <div class="px-4 py-6">
        <HeadingSmall
            title="Settings"
            description="Manage your profile and account settings"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav
                    class="flex flex-col space-y-1 space-x-0"
                    aria-label="Settings"
                >
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="item.href"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            urlIsActive(item.href)
                                ? 'bg-blue-50 text-blue-700 hover:bg-blue-50 hover:text-blue-700'
                                : 'text-muted-foreground',
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1">
                <section class="max-w-2xl space-y-12 pt-16 pl-30">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
