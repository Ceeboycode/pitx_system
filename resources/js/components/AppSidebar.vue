<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { BookOpen, Building2, User, BusFront, House, MessageCircleQuestion } from 'lucide-vue-next'
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

export interface Item {
    id: string
    title: string
    href: string
    permission?: string
}

export interface NavItem {
    id: string
    title: string
    href: string
    icon: any
    permission?: string
    items: Item[]
}

export interface NavFooterItem {
    title: string
    href: string
    icon: any
}

const mainNavItems: NavItem[] = [
    {
        id: 'home',
        title: 'Home',
        href: '#',
        icon: House,
        items: [
            {
                id: 'dashboard',
                title: 'Dashboard',
                href: dashboard().url,
            },
        ],
    },
    {
        id: 'company_vehicle',
        title: 'Company & Vehicle',
        href: '#',
        icon: BusFront,
        items: [
            {
                id: 'vehicle_types',
                title: 'Vehicles Types',
                href: vehicleTypesIndex().url,
            },
            {
                id: 'companies',
                title: 'Companies',
                href: companiesIndex().url,
            },
            {
                id: 'vehicles',
                title: 'Vehicles',
                href: vehiclesIndex().url,
            },
        ],
    },
    {
        id: 'gates_routes',
        title: 'Gates & Routes',
        href: '#',
        icon: Building2,
        items: [
            {
                id: 'gates',
                title: 'Gates',
                href: gateIndex().url,
            },
            {
                id: 'routes',
                title: 'Routes',
                href: routesIndex().url,
            },
            {
                id: 'route_stops',
                title: 'Route Stops',
                href: routeStopsIndex().url,
            },
        ],
    },
    {
        id: 'accounts',
        title: 'Accounts',
        href: '#',
        icon: User,
        items: [
            {
                id: 'users',
                title: 'Users',
                href: usersIndex().url,
                permission: 'users.viewAny',
            },
            {
                id: 'roles',
                title: 'Roles',
                href: rolesIndex().url,
                permission: 'roles.viewAny',
            },
        ],
    },
]

const visibleMainNavItems = computed(() =>
    mainNavItems
        .filter((item) => !item.permission || can(item.permission))
        .map((item) => ({
            ...item,
            items: item.items.filter((sub) => !sub.permission || can(sub.permission)),
        }))
        .filter((item) => item.items.length > 0)
)

const footerNavItems: NavFooterItem[] = [
    {
        title: 'FAQ',
        href: '/faq',
        icon: MessageCircleQuestion,
    },
    {
        title: 'Tutorial',
        href: '#',
        icon: BookOpen,
    },
]
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="group">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child class="gap-0
                        group-data-[state=expanded]:gap-2"
                    >
                        <Link :href="dashboard().url" class="my-2 mx-auto">
                            <AppLogo/>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="visibleMainNavItems"/>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
