<script setup lang="ts">
import type { User } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import MessagingPanel from '@/components/MessagingPanel.vue';
import NotificationDropdown from '@/components/NotificationDropdown.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { Sheet, SheetContent } from '@/components/ui/sheet';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';

import { externalMyActivity } from '@/actions/App/Http/Controllers/AuditLogController';
import CompanyProfileController from '@/actions/App/Http/Controllers/CompanyProfileController';
import CompanyUserController from '@/actions/App/Http/Controllers/CompanyUserController';
import CompanyVehicleController from '@/actions/App/Http/Controllers/CompanyVehicleController';
import { can } from '@/lib/can';
import { logout } from '@/routes';

import {
    Building2,
    BusFront,
    ChevronRight,
    FileText,
    LayoutDashboard,
    LogOut,
    Menu,
    Settings,
    Truck,
    User2,
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';

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

type FlashProps = {
    success?: string | null;
    error?: string | null;
    info?: string | null;
    warning?: string | null;
};

type NotificationItem = {
    id: string;
    type: string | null;
    title: string;
    message: string;
    read_at: string | null;
    created_at: string | null;
    data: Record<string, unknown>;
};

type NotificationsPayload = {
    unread_count: number;
    items: NotificationItem[];
};

type PageProps = {
    name: string;
    quote: {
        message: string;
        author: string;
    };
    sidebarOpen: boolean;
    auth: {
        user: SharedUser;
        company: SharedCompany;
        permissions?: string[];
    };
    notifications?: NotificationsPayload;
    flash?: FlashProps;
};

const page = usePage<PageProps>();

const currentUrl = computed(() => page.url);
const user = computed(() => page.props.auth?.user);
const company = computed(() => page.props.auth?.company);
const notifications = computed(() => page.props.notifications);

function isActive(href: string) {
    return currentUrl.value.startsWith(href);
}

type NavItem = {
    label: string;
    icon: any;
    href: string;
    permission?: string;
};

const navItems: NavItem[] = [
    {
        label: 'Dashboard',
        icon: LayoutDashboard,
        href: '/company/dashboard',
    },
    {
        label: 'Dispatches',
        icon: BusFront,
        href: '/company/dispatches',
        permission: 'external_dispatches.viewAny',
    },
    {
        label: 'Registered Vehicles',
        icon: Truck,
        href: CompanyVehicleController.index().url,
        permission: 'external_vehicles.viewAny',
    },
    {
        label: 'Employee',
        icon: FileText,
        href: CompanyUserController.index().url,
        permission: 'external_users.viewAny',
    },
    {
        label: 'Company Profile',
        icon: Building2,
        href: CompanyProfileController.show().url,
        permission: 'external_companies_settings.view',
    },
    {
        label: 'Activity Logs',
        icon: FileText,
        href: externalMyActivity().url,
    },
    {
        label: 'Settings',
        icon: Settings,
        href: '/company/settings/profile',
    },
];

const visibleNavItems = computed(() =>
    navItems.filter((item) => !item.permission || can(item.permission)),
);

const mobileOpen = ref(false);
const imgError = ref(false);
const userAvatarError = ref(false);

const logoSrc = computed(() => company.value?.logo_url ?? null);
const userAvatarSrc = computed(() => user.value?.avatar ?? null);

watch(logoSrc, () => {
    imgError.value = false;
});

watch(userAvatarSrc, () => {
    userAvatarError.value = false;
});

const showImage = computed(() => !!logoSrc.value && !imgError.value);
const showUserAvatar = computed(
    () => !!userAvatarSrc.value && !userAvatarError.value,
);

const userInitials = computed(() => {
    const name = user.value?.name ?? '';

    return (
        name
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map((word) => word[0])
            .join('')
            .toUpperCase() || 'U'
    );
});

const companyInitials = computed(() => {
    const source =
        company.value?.company_code ?? company.value?.company_name ?? '';

    return (
        source
            .replace(/[^A-Za-z0-9]/g, '')
            .slice(0, 2)
            .toUpperCase() || 'CO'
    );
});

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;

        if (flash.success) toast.success(flash.success);
        if (flash.error) toast.error(flash.error);
        if (flash.info) toast.info(flash.info);
        if (flash.warning) toast.warning(flash.warning);
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <TooltipProvider :delay-duration="200">
        <div class="flex min-h-screen bg-muted/30">
            <!-- Desktop Sidebar -->
            <aside
                class="sticky top-0 hidden h-screen w-60 shrink-0 overflow-y-auto border-r bg-background lg:flex lg:flex-col"
            >
                <!-- Brand / Logo -->
                <div class="flex h-16 items-center gap-3 border-b px-4">
                    <div
                        class="relative h-9 w-9 shrink-0 overflow-hidden rounded-xl border bg-muted shadow-sm"
                    >
                        <img
                            v-if="showImage"
                            :src="logoSrc!"
                            :alt="company?.company_name ?? 'Company'"
                            class="h-full w-full object-cover"
                            @error="imgError = true"
                        />
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center bg-primary text-[11px] font-bold tracking-wide text-primary-foreground select-none"
                        >
                            {{ companyInitials }}
                        </div>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm leading-none font-semibold">
                            {{
                                company?.company_code ??
                                company?.company_name ??
                                'Company Portal'
                            }}
                        </p>
                        <p class="mt-0.5 text-[10px] text-muted-foreground">
                            Company Portal
                        </p>
                    </div>
                </div>

                <!-- Notifications & Messaging -->
                <div class="border-b px-3 py-3">
                    <div
                        class="flex items-center justify-between rounded-xl border bg-background px-3 py-2"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">
                                Notifications
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                Company alerts and updates
                            </p>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <MessagingPanel />
                            <NotificationDropdown />
                        </div>
                    </div>
                </div>

                <!-- Nav links -->
                <nav class="flex-1 space-y-0.5 px-2 py-3">
                    <Link
                        v-for="item in visibleNavItems"
                        :key="item.label"
                        :href="item.href"
                        :class="[
                            'group flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors',
                            isActive(item.href)
                                ? 'bg-primary/10 font-medium text-primary'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                    >
                        <component
                            :is="item.icon"
                            :class="[
                                'h-4 w-4 shrink-0 transition-colors',
                                isActive(item.href)
                                    ? 'text-primary'
                                    : 'text-muted-foreground group-hover:text-foreground',
                            ]"
                        />
                        {{ item.label }}
                        <ChevronRight
                            v-if="isActive(item.href)"
                            class="ml-auto h-3.5 w-3.5 text-primary/60"
                        />
                    </Link>
                </nav>

                <Separator />

                <!-- User footer -->
                <div class="p-3">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-sm transition-colors hover:bg-muted focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <Avatar class="h-7 w-7 shrink-0">
                                    <AvatarImage
                                        v-if="showUserAvatar"
                                        :src="userAvatarSrc!"
                                        :alt="user?.name ?? 'User'"
                                        @error="userAvatarError = true"
                                    />
                                    <AvatarFallback class="text-[11px]">
                                        {{ userInitials }}
                                    </AvatarFallback>
                                </Avatar>

                                <div class="min-w-0 flex-1 text-left">
                                    <p
                                        class="truncate text-xs leading-none font-medium"
                                    >
                                        {{ user?.name ?? 'User' }}
                                    </p>
                                    <p
                                        class="mt-0.5 truncate text-[10px] text-muted-foreground"
                                    >
                                        {{ user?.email ?? '—' }}
                                    </p>
                                </div>
                            </button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent
                            align="end"
                            side="top"
                            class="w-52"
                        >
                            <DropdownMenuLabel
                                class="text-xs font-normal text-muted-foreground"
                            >
                                Signed in as
                                <span
                                    class="block font-semibold text-foreground"
                                >
                                    {{ user?.username ?? '—' }}
                                </span>
                            </DropdownMenuLabel>

                            <DropdownMenuSeparator />

                            <DropdownMenuItem as-child>
                                <Link href="/profile" class="cursor-pointer">
                                    <User2 class="mr-2 h-4 w-4" />
                                    Profile
                                </Link>
                            </DropdownMenuItem>

                            <DropdownMenuSeparator />

                            <DropdownMenuItem
                                as-child
                                class="text-destructive focus:text-destructive"
                            >
                                <Link
                                    :href="logout().url"
                                    method="post"
                                    as="button"
                                    class="flex w-full cursor-pointer items-center"
                                >
                                    <LogOut class="mr-2 h-4 w-4" />
                                    Log out
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </aside>

            <!-- Mobile Sheet -->
            <Sheet v-model:open="mobileOpen">
                <SheetContent side="left" class="w-64 p-0">
                    <div class="flex h-16 items-center gap-3 border-b px-4">
                        <div
                            class="relative h-8 w-8 shrink-0 overflow-hidden rounded-lg border bg-muted"
                        >
                            <img
                                v-if="showImage"
                                :src="logoSrc!"
                                :alt="company?.company_name ?? 'Company'"
                                class="h-full w-full object-cover"
                                @error="imgError = true"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center bg-primary text-[10px] font-bold text-primary-foreground select-none"
                            >
                                {{ companyInitials }}
                            </div>
                        </div>

                        <div class="min-w-0">
                            <p
                                class="truncate text-sm leading-none font-semibold"
                            >
                                {{
                                    company?.company_code ??
                                    company?.company_name ??
                                    'Company Portal'
                                }}
                            </p>
                            <p class="mt-0.5 text-[10px] text-muted-foreground">
                                Company Portal
                            </p>
                        </div>
                    </div>

                    <div class="border-b px-4 py-3">
                        <div
                            class="flex items-center justify-between rounded-xl border bg-background px-3 py-2"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">
                                    Notifications
                                </p>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    Company alerts and updates
                                </p>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <MessagingPanel />
                                <NotificationDropdown />
                            </div>
                        </div>
                    </div>

                    <nav class="space-y-0.5 px-2 py-3">
                        <Link
                            v-for="item in visibleNavItems"
                            :key="item.label"
                            :href="item.href"
                            @click="mobileOpen = false"
                            :class="[
                                'group flex items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-colors',
                                isActive(item.href)
                                    ? 'bg-primary/10 font-medium text-primary'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                            ]"
                        >
                            <component
                                :is="item.icon"
                                class="h-4 w-4 shrink-0"
                            />
                            {{ item.label }}
                        </Link>
                    </nav>

                    <Separator />

                    <div class="p-4">
                        <div class="mb-3 flex items-center gap-3">
                            <Avatar class="h-8 w-8">
                                <AvatarImage
                                    v-if="showUserAvatar"
                                    :src="userAvatarSrc!"
                                    :alt="user?.name ?? 'User'"
                                    @error="userAvatarError = true"
                                />
                                <AvatarFallback class="text-xs">
                                    {{ userInitials }}
                                </AvatarFallback>
                            </Avatar>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">
                                    {{ user?.name ?? 'User' }}
                                </p>
                                <p
                                    class="truncate text-[11px] text-muted-foreground"
                                >
                                    {{ user?.email ?? '—' }}
                                </p>
                            </div>
                        </div>

                        <Link
                            :href="logout().url"
                            method="post"
                            as="button"
                            class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-destructive transition-colors hover:bg-destructive/10"
                        >
                            <LogOut class="h-4 w-4" />
                            Log out
                        </Link>
                    </div>
                </SheetContent>
            </Sheet>

            <!-- Main content -->
            <div class="flex min-w-0 flex-1 flex-col">
                <!-- Mobile top bar -->
                <header
                    class="sticky top-0 z-10 flex h-14 items-center justify-between border-b bg-background/95 px-4 backdrop-blur lg:hidden"
                >
                    <Button
                        variant="ghost"
                        size="icon"
                        @click="mobileOpen = true"
                    >
                        <Menu class="h-5 w-5" />
                    </Button>

                    <div class="flex items-center gap-2">
                        <div
                            class="relative h-6 w-6 shrink-0 overflow-hidden rounded-md border bg-muted"
                        >
                            <img
                                v-if="showImage"
                                :src="logoSrc!"
                                :alt="company?.company_name ?? 'Company'"
                                class="h-full w-full object-cover"
                                @error="imgError = true"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center bg-primary text-[9px] font-bold text-primary-foreground select-none"
                            >
                                {{ companyInitials }}
                            </div>
                        </div>

                        <span class="text-sm font-semibold">
                            {{
                                company?.company_code ??
                                company?.company_name ??
                                'Company Portal'
                            }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <MessagingPanel />
                        <NotificationDropdown />

                        <Avatar class="h-7 w-7">
                            <AvatarImage
                                v-if="showUserAvatar"
                                :src="userAvatarSrc!"
                                :alt="user?.name ?? 'User'"
                                @error="userAvatarError = true"
                            />
                            <AvatarFallback class="text-[11px]">
                                {{ userInitials }}
                            </AvatarFallback>
                        </Avatar>
                    </div>
                </header>

                <!-- Page slot -->
                <main class="flex-1">
                    <slot />
                    <Toaster position="top-right" />
                </main>
            </div>
        </div>
    </TooltipProvider>
</template>
