<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { BookOpen, User, LayoutGrid, Folder } from 'lucide-vue-next'

import NavFooter from '@/components/NavFooter.vue'
import NavMain from '@/components/NavMain.vue'
import NavUser from '@/components/NavUser.vue'
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar'
import AppLogo from './AppLogo.vue'
import { can } from '@/lib/can'

// Routes
import { dashboard } from '@/routes'
import { index as usersIndex } from '@/routes/users'
import { index as rolesIndex } from '@/routes/roles'

export interface NavItem {
    title: string
    href: string
    icon: any
    permission?: string
}

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
        icon: LayoutGrid,
    },
    {
        title: 'Users',
        href: usersIndex().url,
        icon: User,
        permission: 'users.viewAny',
    },
    {
        title: 'Roles',
        href: rolesIndex().url,
        icon: User,
        permission: 'roles.viewAny',
    }
]

const visibleMainNavItems = computed(() =>
    mainNavItems.filter(item => !item.permission || can(item.permission))
)

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
]
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="visibleMainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
