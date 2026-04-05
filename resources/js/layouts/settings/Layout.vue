<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useActiveUrl } from '@/composables/useActiveUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editPassword } from '@/routes/user-password';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Personal Profile',
        href: editProfile(),
    },
    {
        title: 'Password',
        href: editPassword(),
    },
    {
        title: 'Appearance',
        href: editAppearance(),
    },
];

const { urlIsActive } = useActiveUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            title="Settings"
            description="Manage your profile and account settings"
        />

        <Separator class="mb-6" />

        <div class="flex flex-col lg:flex-row lg:gap-12">

            <!-- Sidebar -->
            <aside class="w-full lg:w-52 shrink-0">
                <nav class="flex flex-col gap-0.5" aria-label="Settings">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            urlIsActive(item.href)
                                ? 'bg-blue-50 text-blue-800 hover:bg-blue-50 border-l-2 border-blue-800 rounded-l-none'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4 shrink-0" />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="lg:hidden" />

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <section class="max-w-xl space-y-6">
                    <slot />
                </section>
            </div>

        </div>
    </div>
</template>