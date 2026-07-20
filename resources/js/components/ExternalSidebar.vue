<script setup lang="ts">
import { externalMyActivity } from '@/actions/App/Http/Controllers/AuditLogController';
import CompanyProfileController from '@/actions/App/Http/Controllers/CompanyProfileController';
import CompanyUserController from '@/actions/App/Http/Controllers/CompanyUserController';
import CompanyVehicleController from '@/actions/App/Http/Controllers/CompanyVehicleController';
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
import { logout } from '@/routes';
import type { User } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import type { Component } from 'vue';
import {
    RiArrowDownSLine,
    RiArrowRightSLine,
    RiBuildingLine,
    RiBus2Line,
    RiDashboardHorizontalLine,
    RiHistoryLine,
    RiLogoutBoxLine,
    RiMoonLine,
    RiSettings5Line,
    RiSunLine,
} from 'vue-remix-icons';

type NavHref = NonNullable<InertiaLinkProps['href']>;

type SharedUser = User & {
    username?: string | null;
    type?: string | null;
    status?: string | null;
};

type SharedCompany = {
    id: number;
    company_name: string;
    company_code?: string | null;
    status: string;
    logo_url?: string | null;
} | null;

type PageProps = {
    auth: {
        user: SharedUser;
        company: SharedCompany;
        permissions?: string[];
    };
};

interface NavItem {
    id: string;
    title: string;
    href?: NavHref;
    icon: Component;
    permission?: string;
    items?: NavChildItem[];
}

interface NavChildItem {
    id: string;
    title: string;
    href: NavHref;
    permission?: string;
}

const mainNavItems: NavItem[] = [
    {
        id: 'dashboard',
        title: 'Dashboard',
        href: '/company/dashboard',
        icon: RiDashboardHorizontalLine,
    },
    {
        id: 'company-management',
        title: 'Management',
        href: '/',
        icon: RiBuildingLine,
        permission: 'external_dispatches.viewAny',
        items: [
            {
                id: 'company-profile',
                title: 'Company Profile',
                href: CompanyProfileController.show().url,
                permission: 'external_companies_settings.view',
            },
            {
                id: 'employee',
                title: 'Employees',
                href: CompanyUserController.index().url,
                permission: 'external_users.viewAny',
            },
        ],
    },
    {
        id: 'company-operations',
        title: 'Operations',
        href: '/',
        icon: RiBus2Line,
        items: [
            {
                id: 'vehicles',
                title: 'Vehicle Units',
                href: '/company/vehicles',
                permission: 'external_vehicles.viewAny',
            },
            {
                id: 'dispatches',
                title: 'Dispatches',
                href: '/company/dispatches',
                permission: 'external_dispatches.viewAny',
            },
        ],
    },
];

const footerNavItems: NavItem[] = [
    {
        id: 'activity-logs',
        title: 'Activity Logs',
        href: externalMyActivity().url,
        icon: RiHistoryLine,
    },
];

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);
const company = computed(() => page.props.auth.company);
const { getInitials } = useInitials();
const { state, isMobile, openMobile, setOpen, setOpenMobile } = useSidebar();
const { resolvedAppearance, updateAppearance } = useAppearance();

const imgError = ref(false);
const userAvatarError = ref(false);
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

const visibleMainNavItems = computed(() => visibleItems(mainNavItems));

const visibleFooterNavItems = computed(() => visibleItems(footerNavItems));

const isCollapsed = computed(() => state.value === 'collapsed');
const isDark = computed(() => resolvedAppearance.value === 'dark');
const logoSrc = computed(() => company.value?.logo_url ?? null);
const userAvatarSrc = computed(() => user.value?.avatar ?? null);

const showImage = computed(() => !!logoSrc.value && !imgError.value);
const showUserAvatar = computed(() => !!userAvatarSrc.value && !userAvatarError.value);

const companyInitials = computed(() => {
    const source = company.value?.company_code ?? company.value?.company_name ?? '';
    return source.replace(/[^A-Za-z0-9]/g, '').slice(0, 2).toUpperCase() || 'CO';
});

watch(logoSrc, () => {
    imgError.value = false;
});

watch(userAvatarSrc, () => {
    userAvatarError.value = false;
});

function hrefPath(href: NavHref) {
    return toUrl(href).split('#')[0];
}

function isHrefActive(href: NavHref) {
    return currentPath.value === hrefPath(href) || currentPath.value.startsWith(`${hrefPath(href)}/`);
}

function visibleItems(items: NavItem[]) {
    return items
        .filter((item) => !item.permission || can(item.permission))
        .map((item) => ({
            ...item,
            items: item.items?.filter((subItem) => !subItem.permission || can(subItem.permission)) ?? [],
        }))
        .filter((item) => item.href || item.items.length > 0);
}

function itemHref(item: NavItem) {
    return item.href ?? item.items?.[0]?.href ?? '#';
}

function isItemActive(item: NavItem) {
    return (
        (item.href ? isHrefActive(item.href) : false) ||
        !!item.items?.some((subItem) => isHrefActive(subItem.href))
    );
}

function isItemExpanded(item: NavItem) {
    return expandedItems.value.has(item.id) || isItemActive(item);
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

function shouldExpandFromCollapsed() {
    return isCollapsed.value && !isMobile.value;
}

function closeMobileSidebar() {
    if (isMobile.value) {
        setOpenMobile(false);
    }
}

function shouldUseParentButton(item: NavItem) {
    return shouldExpandFromCollapsed() || ((item.items?.length ?? 0) > 1 && !isCollapsed.value);
}

function handleParentClick(item: NavItem) {
    if (shouldExpandFromCollapsed()) {
        setOpen(true);

        if ((item.items?.length ?? 0) > 1) {
            const nextExpanded = new Set(expandedItems.value);
            nextExpanded.add(item.id);
            expandedItems.value = nextExpanded;
        }

        return;
    }

    if ((item.items?.length ?? 0) > 1 && !isCollapsed.value) {
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
                    href="/company/dashboard"
                    :class="cn(
                        'flex min-h-10 shrink-0 items-center rounded-md text-custom-shadow',
                        isCollapsed ? 'mx-auto size-10 justify-center rounded-full p-0' : 'gap-3 px-2',
                    )"
                    aria-label="Open company dashboard"
                    @click="closeMobileSidebar"
                >
                    <div class="relative size-10 shrink-0 overflow-hidden rounded-full border border-custom-bg-dark bg-custom-bg dark:border-custom-bg-light dark:bg-custom-bg-light">
                        <img
                            v-if="showImage"
                            :src="logoSrc!"
                            :alt="company?.company_name ?? 'Company'"
                            class="h-full w-full object-cover"
                            @error="imgError = true"
                        />
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center bg-custom-primary text-xs font-semibold text-custom-bg-light dark:text-custom-shadow"
                        >
                            {{ companyInitials }}
                        </div>
                    </div>

                    <div :class="cn('min-w-0 flex-1', isCollapsed && 'hidden')">
                        <p class="truncate text-sm font-semibold text-custom-body">
                            {{ company?.company_code ?? company?.company_name ?? 'Company Portal' }}
                        </p>
                        <p class="truncate text-xs text-custom-shadow/80">Company Portal</p>
                    </div>
                </Link>

                <div class="flex min-h-0 flex-1 flex-col justify-between gap-3">
                    <div class="flex min-h-0 flex-1 flex-col gap-1 overflow-y-auto overflow-x-hidden no-scrollbar">
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
                                    :is="shouldUseParentButton(item) ? 'button' : Link"
                                    :href="shouldUseParentButton(item) ? undefined : itemHref(item)"
                                    :title="item.title"
                                    :type="shouldUseParentButton(item) ? 'button' : undefined"
                                    :class="cn(
                                        'flex min-h-10 w-full cursor-pointer items-center rounded-md px-3 py-2 text-sm text-custom-shadow transition-colors hover:bg-custom-secondary/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50 dark:hover:bg-custom-secondary/20',
                                        isCollapsed ? 'mx-auto size-10 justify-center rounded-full p-0 lg:gap-0' : 'gap-2',
                                        isItemActive(item) && 'bg-custom-secondary/10 dark:bg-custom-secondary/20 dark:text-custom-shadow',
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
                                        v-if="(item.items?.length ?? 0) > 1"
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
                                    v-if="(item.items?.length ?? 0) > 1 && isItemExpanded(item) && !isCollapsed"
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
                                                    ? 'bg-custom-primary text-custom-bg-light dark:text-custom-shadow'
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
                                'min-h-10 w-full cursor-pointer justify-start rounded-md px-3 py-2 text-custom-shadow transition-all duration-300 hover:bg-custom-secondary/10 dark:hover:bg-custom-secondary/20',
                                isCollapsed && 'mx-auto size-10 justify-center rounded-full p-0',
                            )"
                            @click="toggleTheme"
                        >
                            <RiSunLine class="hidden size-4 dark:block" aria-hidden="true" />
                            <RiMoonLine class="size-4 dark:hidden" aria-hidden="true" />
                            <span :class="cn('truncate pl-1', isCollapsed && 'hidden')">Theme</span>
                        </Button>

                        <nav class="flex flex-col gap-0 overflow-hidden">
                            <div
                                v-for="item in visibleFooterNavItems"
                                :key="item.id"
                            >
                                <component
                                    :is="shouldUseParentButton(item) ? 'button' : Link"
                                    :href="shouldUseParentButton(item) ? undefined : itemHref(item)"
                                    :title="item.title"
                                    :type="shouldUseParentButton(item) ? 'button' : undefined"
                                    :class="cn(
                                        'flex min-h-10 w-full cursor-pointer items-center rounded-md px-3 py-2 text-sm text-custom-shadow transition-colors hover:bg-custom-secondary/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50 dark:hover:bg-custom-secondary/20',
                                        isCollapsed ? 'mx-auto size-10 justify-center rounded-full p-0 lg:gap-0' : 'gap-2',
                                        isItemActive(item) && 'bg-custom-secondary/10 text-custom-primary dark:bg-custom-secondary/20 dark:text-custom-shadow',
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
                                        v-if="(item.items?.length ?? 0) > 1"
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
                                    v-if="(item.items?.length ?? 0) > 1 && isItemExpanded(item) && !isCollapsed"
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
                                                    ? 'bg-custom-primary text-custom-bg-light dark:text-custom-shadow'
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

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="float"
                                :class="cn(
                                    'group min-h-fit items-center rounded-3xl border border-custom-bg-dark bg-custom-bg px-3 py-2 shadow-none transition-all duration-300 hover:border-custom-secondary/10 hover:bg-custom-secondary/20 focus-visible:ring-2 focus-visible:ring-ring/50 dark:border-custom-bg-light dark:bg-custom-bg-light',
                                    isCollapsed ? 'mx-auto size-12 justify-center rounded-full p-1' : 'gap-3',
                                )"
                            >
                                <Avatar class="size-10">
                                    <AvatarImage
                                        v-if="showUserAvatar"
                                        :src="userAvatarSrc!"
                                        :alt="user.name"
                                        @error="userAvatarError = true"
                                    />
                                    <AvatarFallback class="bg-custom-primary/20 text-sm font-semibold text-custom-primary dark:bg-custom-primary dark:text-custom-shadow">
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
                            <DropdownMenuItem as-child class="rounded-md hover:bg-custom-secondary/10 hover:text-custom-shadow">
                                <Link
                                    href="/company/settings/profile"
                                    class="flex cursor-pointer flex-row items-center gap-3 px-3 py-2 text-custom-shadow"
                                    @click="closeMobileSidebar"
                                >
                                    <RiSettings5Line class="size-4 text-custom-shadow" />
                                    <span>Settings</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem as-child class="cursor-pointer rounded-md hover:bg-destructive/10 hover:text-destructive">
                                <Link
                                    :href="logout().url"
                                    method="post"
                                    as="button"
                                    class="flex w-full cursor-pointer flex-row items-center gap-3 px-3 py-2 text-left"
                                    data-test="logout-button"
                                    @click="handleLogout"
                                >
                                    <RiLogoutBoxLine class="size-4 hover:text-destructive" />
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
</template>
