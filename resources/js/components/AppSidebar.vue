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
    SidebarRail
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
import { CircleQuestionMark } from 'lucide-react'

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
        title: "Home",
        href: "#",
        icon: House,
        items: [
            {
                id: 'dashboard',
                title: "Dashboard",
                href: dashboard().url,
            }
        ],
    },
    {
        id: 'company_vehicle',
        title: "Company & Vehicle",
        href: "#",
        icon: BusFront,
        items: [
            {
                id: 'vehicle_types',
                title: 'Vehicles Types',
                href: vehicleTypesIndex().url,
                // icon: BusFrontIcon,
            },  
            {
                id: 'companies',
                title: 'Companies',
                href: companiesIndex().url,
                // icon: Building2,
                // permission: 'companies.viewAny',
            },
            {
                id: 'vehicles',
                title: 'Vehicles',
                href: vehiclesIndex().url,
                // icon: Bus,
            }
        ],
    },
    {
        id: 'gates_routes',
        title: "Gates & Routes",
        href: "#",
        icon: Building2,
        items: [
            {
                id: 'gates',
                title: 'Gates',
                href: gateIndex().url,
                // icon: DoorOpen,
            },  
            {
                id: 'routes',
                title: 'Routes',
                href: routesIndex().url,
                // icon: MapIcon,
            },
            {
                id: 'route_stops',
                title: 'Route Stops',
                href: routeStopsIndex().url,
                // icon: MapPin,
            },
        ],
    },
    {
        id: 'accounts',
        title: "Accounts",
        href: "#",
        icon: User,
        items: [
            {
                id: 'users',
                title: 'Users',
                href: usersIndex().url,
                // icon: User,
                permission: 'users.viewAny',
            },
            {
                id: 'roles',
                title: 'Roles',
                href: rolesIndex().url,
                // icon: User,
                permission: 'roles.viewAny',
            }
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
)

const footerNavItems: NavFooterItem[] = [
    {
        title: 'FAQ',
        href: '#',
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
            <!-- <NavMain :items={data.navMain} /> -->
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
