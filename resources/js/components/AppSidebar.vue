<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useSidebar } from '@/components/ui/sidebar';
import { useAppearance } from '@/composables/useAppearance';
import { useInitials } from '@/composables/useInitials';
import { can } from '@/lib/can';
import { cn, toUrl } from '@/lib/utils';
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
import { edit as editProfile } from '@/routes/profile';
import { index as rolesIndex } from '@/routes/roles';
import { index as routesIndex } from '@/routes/routes';
import { index as usersIndex } from '@/routes/users';
import { index as vehiclesIndex } from '@/routes/vehicles';
import { Link, router, usePage } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { Component } from 'vue';
import {
    RiArrowDownSLine,
    RiArrowRightSLine,
    RiBuildingLine,
    RiBus2Line,
    RiComputerLine,
    RiDashboardHorizontalLine,
    RiMoonLine,
    RiQuestionAnswerLine,
    RiQuestionLine,
    RiRoadMapLine,
    RiSettings5Line,
    RiSunLine,
    RiUser3Line,
} from 'vue-remix-icons';
import PitxLogo from './assets/PITX.png';

type NavHref = NonNullable<InertiaLinkProps['href']>;

interface Item {
    id: string;
    title: string;
    href: NavHref;
    permission?: string;
}

interface NavItem {
    id: string;
    title: string;
    href: NavHref;
    icon: Component;
    permission?: string;
    items: Item[];
}

interface NavFooterItem {
    title: string;
    href: NavHref;
    icon: Component;
}

const mainNavItems: NavItem[] = [
    {
        id: 'home',
        title: 'Home',
        href: '#',
        icon: RiDashboardHorizontalLine,
        items: [
            {
                id: 'dashboard',
                title: 'Dashboard',
                href: dashboard().url,
            },
        ],
    },
    {
        id: 'gates_routes',
        title: 'Gates & Routes',
        href: '#',
        icon: RiBuildingLine,
        items: [
            {
                id: 'gates',
                title: 'Gates',
                href: gateIndex().url,
                permission: 'gates.viewAny',
            },
            {
                id: 'routes',
                title: 'Routes',
                href: routesIndex().url,
                permission: 'routes.viewAny',
            },
        ],
    },
    {
        id: 'company_vehicle',
        title: 'Companies & Vehicles',
        href: '#',
        icon: RiBus2Line,
        items: [
            {
                id: 'companies',
                title: 'Companies',
                href: companiesIndex().url,
                permission: 'companies.viewAny',
            },
            {
                id: 'vehicles',
                title: 'Vehicles',
                href: vehiclesIndex().url,
                permission: 'vehicles.viewAny',
            },
        ],
    },
    {
        id: 'dispatches',
        title: 'Dispatches',
        href: '#',
        icon: RiRoadMapLine,
        items: [
            {
                id: 'dispatches',
                title: 'Dispatches',
                href: dispatchesIndex().url,
                permission: 'dispatches.viewAny',
            },
            {
                id: 'change-requests',
                title: 'Change Requests',
                href: changeRequestsIndex().url,
                permission: 'dispatches.viewAny',
            },
        ],
    },
    {
        id: 'crm',
        title: 'Customer Relations',
        href: '#',
        icon: RiQuestionAnswerLine,
        items: [
            {
                id: 'threads',
                title: 'Reports',
                href: crmIndex().url,
            },
        ],
    },
    {
        id: 'accounts',
        title: 'Accounts',
        href: '#',
        icon: RiUser3Line,
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
    {
        id: 'system',
        title: 'System',
        href: '#',
        icon: RiComputerLine,
        items: [
            {
                id: 'audit-logs',
                title: 'Audit Logs',
                href: auditLogsIndex().url,
                permission: 'audit_logs.viewAny',
            },
            {
                id: 'my-activity-logs',
                title: 'My Activity Logs',
                href: myActivityLogsIndex().url,
                permission: 'audit_logs.viewOwn',
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
    {
        title: 'FAQ',
        href: '/faq',
        icon: RiQuestionLine,
    },
];

const page = usePage();
const user = computed(() => page.props.auth.user);
const { getInitials } = useInitials();
const { state, isMobile, openMobile, setOpenMobile } = useSidebar();
const { resolvedAppearance, updateAppearance } = useAppearance();
const expandedItems = ref<Set<string>>(new Set());
const sidebarWidth = 72;
const sidebarMobileWidth = 72;
const sidebarIconWidth = 24;

const sidebarStyle = {
    '--sidebar-width': `${sidebarWidth * 4}px`,
    '--sidebar-width-mobile': `${sidebarMobileWidth * 4}px`,
    '--sidebar-width-icon': `${sidebarIconWidth * 4}px`,
};

const currentPath = computed(() => {
    if (typeof window === 'undefined') {
        return page.url;
    }

    return new URL(page.url, window.location.origin).pathname;
});

const isCollapsed = computed(() => state.value === 'collapsed');
const isDark = computed(() => resolvedAppearance.value === 'dark');

function hrefPath(href: NavHref) {
    return toUrl(href).split('#')[0];
}

function isHrefActive(href: NavHref) {
    return currentPath.value === hrefPath(href);
}

function isItemActive(item: NavItem) {
    return isHrefActive(item.href) || item.items.some((subItem) => isHrefActive(subItem.href));
}

function isItemExpanded(item: NavItem) {
    return expandedItems.value.has(item.id) || isItemActive(item);
}

function closeMobileSidebar() {
    if (isMobile.value) {
        setOpenMobile(false);
    }
}

function toggleExpanded(item: NavItem) {
    const nextExpanded = new Set(expandedItems.value);

    if (nextExpanded.has(item.id)) {
        nextExpanded.delete(item.id);
    } else {
        nextExpanded.add(item.id);
    }

    expandedItems.value = nextExpanded;
}

function handleParentClick(item: NavItem) {
    if (item.items.length > 1 && !isCollapsed.value) {
        toggleExpanded(item);
        return;
    }

    closeMobileSidebar();
}

function toggleTheme() {
    updateAppearance(isDark.value ? 'light' : 'dark');
}

function handleLogout() {
    router.flushAll();
}
</script>

<template>
    <div
        class="group peer text-custom-shadow"
        :style="sidebarStyle"
        data-slot="sidebar"
        :data-state="state"
        :data-collapsible="isCollapsed ? 'icon' : ''"
        data-variant="sidebar"
        data-side="left"
    >
        <div
            :class="cn(
                'relative hidden bg-transparent transition-[width] duration-200 ease-linear md:block',
                isCollapsed ? 'w-(--sidebar-width-icon)' : 'w-(--sidebar-width)',
            )"
        />

        <aside
            :class="cn(
                'fixed inset-y-0 left-0 z-50 flex h-svh w-(--sidebar-width-mobile) flex-col border-none bg-custom-bg-light p-3 shadow-none transition-[transform,width] duration-200 ease-linear dark:bg-custom-bg md:z-10 md:w-(--sidebar-width)',
                openMobile ? 'translate-x-0' : '-translate-x-full',
                'md:translate-x-0',
                isCollapsed && 'md:w-(--sidebar-width-icon)',
            )"
        >
        <div class="flex h-full min-h-0 flex-col gap-3 overflow-visible p-3">
            <Link
                :href="dashboard().url"
                :class="cn(
                    'flex h-10 shrink-0 items-center justify-center rounded-md',
                    isCollapsed && 'mx-auto w-fit',
                )"
                aria-label="Open dashboard"
                @click="closeMobileSidebar"
            >
                <div :class="cn('flex items-center', isCollapsed ? 'w-10' : 'max-w-24 flex-1')">
                    <img
                        :src="PitxLogo"
                        alt="PITX Logo"
                        class="w-full object-contain"
                    />
                </div>
            </Link>

            <div class="flex min-h-0 flex-1 flex-col justify-between gap-3">
                <div class="flex min-h-0 flex-1 flex-col gap-1 overflow-y-auto overflow-x-hidden">
                    <span
                        :class="cn(
                            'px-3 pb-1 text-xs text-custom-shadow',
                            isCollapsed && 'hidden',
                        )"
                    >
                        Main
                    </span>

                    <nav class="flex min-h-0 flex-col gap-0">
                        <div
                            v-for="item in visibleMainNavItems"
                            :key="item.id"
                        >
                            <component
                                :is="item.items.length > 1 && !isCollapsed ? 'button' : Link"
                                :href="item.items.length > 1 && !isCollapsed ? undefined : item.items[0]?.href"
                                :title="item.title"
                                :type="item.items.length > 1 && !isCollapsed ? 'button' : undefined"
                                :class="cn(
                                    'flex min-h-10 w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-custom-shadow transition-colors hover:bg-custom-secondary/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50 dark:hover:bg-custom-secondary/20',
                                    isCollapsed && 'mx-auto size-10 justify-center rounded-full p-0 lg:gap-0',
                                    isItemActive(item) && 'bg-custom-secondary/10 dark:bg-custom-secondary/20',
                                )"
                                @click="handleParentClick(item)"
                            >
                                <component :is="item.icon" class="size-4 shrink-0" />
                                <span
                                    :class="cn(
                                        'flex w-full items-center justify-between truncate pl-1',
                                        isCollapsed && 'hidden',
                                    )"
                                >
                                    {{ item.title }}
                                </span>
                                <span
                                    v-if="item.items.length > 1"
                                    :class="cn('flex shrink-0', isCollapsed && 'hidden')"
                                >
                                    <RiArrowDownSLine
                                        v-if="isItemExpanded(item)"
                                        class="size-4"
                                    />
                                    <RiArrowRightSLine v-else class="size-4" />
                                </span>
                            </component>

                            <div
                                v-if="item.items.length > 1 && isItemExpanded(item) && !isCollapsed"
                                class="flex flex-row gap-2 pl-2"
                            >
                                <div class="ml-3 w-0.25 bg-custom-shadow/80" />
                                <div class="flex w-full flex-col">
                                    <Link
                                        v-for="subItem in item.items"
                                        :key="subItem.id"
                                        :href="subItem.href"
                                        :title="subItem.title"
                                        :class="cn(
                                            'flex items-center rounded-md px-3 py-2 text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50',
                                            isHrefActive(subItem.href)
                                                ? 'bg-custom-primary text-custom-bg-light'
                                                : 'text-custom-shadow hover:bg-custom-secondary/10 dark:hover:bg-custom-secondary/20',
                                        )"
                                        @click="closeMobileSidebar"
                                    >
                                        {{ subItem.title }}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </nav>
                </div>

                <div class="flex shrink-0 flex-col gap-0">
                    <span
                        :class="cn(
                            'px-3 pb-1 text-xs text-custom-shadow',
                            isCollapsed && 'hidden',
                        )"
                    >
                        Other
                    </span>

                    <Button
                        type="button"
                        variant="default"
                        size="icon"
                        :class="cn(
                            'min-h-10 w-full justify-start rounded-md px-3 py-2 text-custom-shadow transition-colors hover:bg-custom-secondary/10 dark:hover:bg-custom-secondary/20',
                            isCollapsed && 'mx-auto size-10 justify-center rounded-full p-0',
                        )"
                        @click="toggleTheme"
                    >
                        <RiSunLine class="hidden size-4 dark:block" aria-hidden="true" />
                        <RiMoonLine class="size-4 dark:hidden" aria-hidden="true" />
                        <span :class="cn('truncate pl-1', isCollapsed && 'hidden')">
                            Theme
                        </span>
                    </Button>

                    <nav class="flex flex-col gap-0 overflow-hidden">
                        <Link
                            v-for="item in footerNavItems"
                            :key="item.title"
                            :href="item.href"
                            :title="item.title"
                            :class="cn(
                                'flex min-h-10 w-full items-center rounded-md px-3 py-2 text-sm text-custom-shadow transition-colors hover:bg-custom-secondary/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50 dark:hover:bg-custom-secondary/20',
                                isCollapsed ? 'mx-auto size-10 justify-center rounded-full p-0 lg:gap-0' : 'gap-2',
                                isHrefActive(item.href) && 'bg-custom-secondary/10 text-custom-primary',
                            )"
                            @click="closeMobileSidebar"
                        >
                            <component :is="item.icon" class="size-4 shrink-0" />
                            <span :class="cn('truncate pl-1', isCollapsed && 'hidden')">
                                {{ item.title }}
                            </span>
                        </Link>
                    </nav>
                </div>

                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="float"
                            :class="cn(
                                'group min-h-fit items-center rounded-md border border-custom-bg-dark bg-custom-bg px-3 py-2 shadow-none transition-colors hover:border-custom-secondary/10 hover:bg-custom-secondary/20 focus-visible:ring-2 focus-visible:ring-ring/50 dark:border-custom-bg-light dark:bg-custom-bg-light',
                                isCollapsed ? 'mx-auto size-12 justify-center rounded-full p-1' : 'gap-3',
                            )"
                        >
                            <Avatar class="size-10">
                                <AvatarImage
                                    v-if="user.avatar"
                                    :src="user.avatar"
                                    :alt="user.name"
                                />
                                <AvatarFallback class="bg-custom-primary/20 text-sm font-semibold text-custom-primary">
                                    {{ getInitials(user.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div :class="cn('min-w-0 flex-1', isCollapsed && 'hidden')">
                                <p class="truncate text-start text-sm font-semibold text-custom-body">
                                    {{ user.name }}
                                </p>
                                <p class="truncate text-start text-xs text-custom-shadow">
                                    {{ user.email }}
                                </p>
                            </div>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="start"
                        :side="isMobile ? 'bottom' : isCollapsed ? 'left' : 'top'"
                        class="mb-2"
                    >
                        <DropdownMenuItem as-child class="rounded-md hover:bg-custom-secondary/20">
                            <Link
                                :href="editProfile()"
                                class="flex cursor-pointer flex-row items-center gap-3 px-3 py-2"
                                @click="closeMobileSidebar"
                            >
                                <RiSettings5Line class="size-4" />
                                <span>Settings</span>
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child class="cursor-pointer rounded-md hover:bg-destructive/10">
                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                class="flex w-full cursor-pointer flex-row items-center gap-3 px-3 py-2 text-left"
                                data-test="logout-button"
                                @click="handleLogout"
                            >
                                <span>Log out</span>
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
        </aside>
    </div>

    <button
        v-if="isMobile && openMobile"
        type="button"
        aria-label="Close navigation overlay"
        class="fixed inset-0 z-40 bg-black/45 md:hidden"
        @click="setOpenMobile(false)"
    />

    <slot />
</template>
