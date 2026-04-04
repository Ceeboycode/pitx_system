<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    Building2,
    BusFront,
    Headset,
    House,
    LayoutList,
    MessageCircleQuestion,
    Shield,
    User,
} from 'lucide-vue-next';
import { computed } from 'vue';

import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
// import NotificationDropdown from '@/components/NotificationDropdown.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { can } from '@/lib/can';
import AppLogo from './AppLogo.vue';

// Wayfinder routes
import {
    index as auditLogsIndex,
    myActivity as myActivityLogsIndex,
} from '@/actions/App/Http/Controllers/AuditLogController';
import { index as changeRequestsIndex } from '@/actions/App/Http/Controllers/DispatchChangeRequestController';
import { index as dispatchesIndex } from '@/actions/App/Http/Controllers/InternalDispatchController';
import { dashboard } from '@/routes';
import { index as companiesIndex } from '@/routes/companies';
import { index as crmIndex } from '@/routes/crm/threads';
import { index as gateIndex } from '@/routes/gates';
import { index as rolesIndex } from '@/routes/roles';
import { index as routesIndex } from '@/routes/routes';
import { index as usersIndex } from '@/routes/users';
import { index as vehiclesIndex } from '@/routes/vehicles';

export interface Item {
    id: string;
    title: string;
    href: string;
    permission?: string;
}

export interface NavItem {
    id: string;
    title: string;
    href: string;
    icon: any;
    permission?: string;
    items: Item[];
}

export interface NavFooterItem {
    title: string;
    href: string;
    icon: any;
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
        id: 'crm',
        title: 'Customer Relations',
        href: '#',
        icon: LayoutList,
        items: [
            {
                id: 'threads',
                title: 'Reports',
                href: crmIndex().url,
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
            },
            {
                id: 'roles',
                title: 'Roles',
                href: rolesIndex().url,
            },
        ],
    },
    {
        id: 'dispatches',
        title: 'Dispatches',
        href: '#',
        icon: BusFront,
        items: [
            {
                id: 'dispatches',
                title: 'Dispatches',
                href: dispatchesIndex().url,
            },
            {
                id: 'change-requests',
                title: 'Change Requests',
                href: changeRequestsIndex().url,
            },
        ],
    },
    {
        id: 'system',
        title: 'System',
        href: '#',
        icon: Shield,
        items: [
            {
                id: 'audit-logs',
                title: 'Audit Logs',
                href: auditLogsIndex().url,
            },
            {
                id: 'my-activity-logs',
                title: 'My Activity Logs',
                href: myActivityLogsIndex().url,
            },
        ],
    },
];

const visibleMainNavItems = computed(() =>
    mainNavItems
        .filter((item) => !item.permission || can(item.permission))
        .map((item) => ({
            ...item,
            items: item.items.filter(
                (sub) => !sub.permission || can(sub.permission),
            ),
        }))
        .filter((item) => item.items.length > 0),
);

const footerNavItems: NavFooterItem[] = [
    // {
    //     title: 'Support',
    //     href: crmIndex().url,
    //     icon: Headset,
    // },
    {
        title: 'FAQ',
        href: '#',
        icon: MessageCircleQuestion,
    },
    // {
    //     title: 'Tutorial',
    //     href: '#',
    //     icon: BookOpen,
    // },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="group">
        <SidebarHeader class="border-b">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="gap-0 group-data-[state=expanded]:gap-2"
                    >
                        <Link :href="dashboard().url" class="mx-auto my-2">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <!-- <div class="px-2 pb-3"> -->
                <!-- <div
                    class="flex items-center justify-between rounded-xl border bg-background px-3 py-2"
                > -->
                    <!-- <div class="min-w-0 group-data-[collapsible=icon]:hidden">
                        <p class="truncate text-sm font-semibold">
                            Notifications
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            Internal alerts and updates
                        </p>
                    </div> -->

                    <!-- <NotificationDropdown /> -->
                <!-- </div> -->
            <!-- </div> -->
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
