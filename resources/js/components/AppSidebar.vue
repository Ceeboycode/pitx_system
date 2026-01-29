<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { BookOpen, User, LayoutGrid, Folder, Building2, BusFrontIcon, MapPin, Bus } from 'lucide-vue-next'
import { MapIcon, DoorOpen } from 'lucide-vue-next'


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
import { index as companiesIndex } from '@/routes/companies'
import { index as vehicleTypesIndex } from '@/routes/vehicle-types'
import { index as routeStopsIndex } from '@/routes/route-stops'
import { index as routesIndex } from '@/routes/routes'
import { index as gateIndex } from '@/routes/gates'
import { index as vehiclesIndex } from '@/routes/vehicles'

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
    },
    {
        title: 'Companies',
        href: companiesIndex().url,
        icon: Building2,
        // permission: 'companies.viewAny',
    },
    {
        title: 'Vehicles Types',
        href: vehicleTypesIndex().url,
        icon: BusFrontIcon,
    },
    {
        title: 'Route Stops',
        href: routeStopsIndex().url,
        icon: MapPin,
    },
    {
        title: 'Routes',
        href: routesIndex().url,
        icon: MapIcon,
    },
    {
        title: 'Gates',
        href: gateIndex().url,
        icon: DoorOpen,
    },
    {
        title: 'Vehicles',
        href: vehiclesIndex().url,
        icon: Bus,
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
