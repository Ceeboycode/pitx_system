<script setup lang="ts">

import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';


import {
    Table,
    TableColumn,
    TableHeader,
    TableContent,
    TableRow,
    TableCard,
    TableData,
    TableMoreButton,
} from '@/components/ui/_table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';

import AppLayout from '@/layouts/AppLayout.vue';
import {
    create,
    index,
    show,
    trash,
} from '@/routes/users';

import {
    ArchiveUserDialog,
    ResetPasswordDialog,
    ToggleUserStatusDialog,
} from '@/components/internal/users';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { PanelLayout, MainPanel, SidePanel } from '@/components/ui/_panels';


import {
    RiAddLine,
    RiArchive2Line,
    RiArrowDownSLine,
    RiArrowUpDownLine,
    RiArrowUpSLine,
    RiCloseLine,
    RiExternalLinkLine,
    RiFilter2Line,
    RiKey2Line,
    RiMore2Line,
    RiShutDownLine,
} from 'vue-remix-icons';


import { computed, onMounted, onUnmounted, ref } from 'vue';


import { can } from '@/lib/can';

const canCreate = can('users.create');
const canToggle = can('users.toggleStatus');
const canResetPass = can('users.resetPassword');
const canViewTrash = can('users.viewTrash');
const canArchive = can('users.archive');


interface Role {
    id: number;
    name: string;
    type: string;
}

interface Company {
    id: number;
    company_name: string;
    company_code: string;
}

interface User {
    id: number;
    username: string;
    name: string;
    email: string;
    email_verified_at: string | null;
    avatar: string | null;
    phone_number: string | null;
    company_id: number | null;
    company: Company | null;
    roles: Role[];
    status: 'active' | 'inactive' | string;
    avatar_url: string | null;
}

type SortField = 'username' | 'name' | 'email' | 'status' | null;
type SortDir = 'asc' | 'desc';


const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: index().url }];


const props = defineProps<{
    users: {
        data: User[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: {
        search?: string | null;
        type?: string | null;
        status?: string | null;
        sort_by?: SortField;
        sort_dir?: SortDir;
    };
    statuses?: string[];
    canSeeSuperAdmin?: boolean;
    currentUserId: number;
}>();


const roleFilter = ref<string>(props.filters.type ?? 'all');
const statusFilter = ref<string>(props.filters.status ?? 'all');
const pendingRoleFilter = ref(roleFilter.value);
const pendingStatusFilter = ref(statusFilter.value);
const filterOpen = ref(false);
const sortBy = ref<SortField>(props.filters.sort_by ?? null);
const sortDir = ref<SortDir>(props.filters.sort_dir ?? 'asc');

const hasActiveFilters = computed(
    () =>
        (roleFilter.value && roleFilter.value !== 'all') ||
        (statusFilter.value && statusFilter.value !== 'all') ||
        sortBy.value !== null,
);

const activeFilterCount = computed(() => {
    let count = 0;
    if (roleFilter.value && roleFilter.value !== 'all') count++;
    if (statusFilter.value && statusFilter.value !== 'all') count++;
    return count;
});

function currentFilterParams(): Record<string, string | undefined> {
    return {
        type: roleFilter.value !== 'all' ? roleFilter.value : undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        sort_by: sortBy.value ?? undefined,
        sort_dir: sortBy.value ? sortDir.value : undefined,
    };
}

function applyFilters(
    overrides: Record<string, string | null | undefined> = {},
) {
    router.get(
        index().url,
        {
            search: props.filters.search ?? undefined,
            ...currentFilterParams(),
            ...overrides,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['users', 'filters', 'flash'],
        },
    );
}

function applyFilterPopover() {
    roleFilter.value = pendingRoleFilter.value;
    statusFilter.value = pendingStatusFilter.value;
    applyFilters();
    filterOpen.value = false;
}

function cancelFilterPopover() {
    pendingRoleFilter.value = roleFilter.value;
    pendingStatusFilter.value = statusFilter.value;
    filterOpen.value = false;
}

function toggleSort(field: SortField) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDir.value = 'asc';
    }
    applyFilters();
}

function clearFilters() {
    roleFilter.value = 'all';
    statusFilter.value = 'all';
    pendingRoleFilter.value = 'all';
    pendingStatusFilter.value = 'all';
    sortBy.value = null;
    sortDir.value = 'asc';

    applyFilters({
        role: undefined,
        status: undefined,
        sort_by: undefined,
        sort_dir: undefined,
    });
    filterOpen.value = false;
}


const showCompanyColumn = computed(() => roleFilter.value !== 'internal');


function roleBadgeClass(role: Role) {
    switch (role.type) {
        case 'internal':
            return 'border-blue-200 bg-blue-100 text-blue-700';
        case 'external':
            return 'border-emerald-200 bg-emerald-100 text-emerald-700';
        default:
            return 'bg-muted text-muted-foreground';
    }
}

function statusBadgeClass(status: string) {
    switch (status) {
        case 'active':
            return 'border-emerald-200 bg-emerald-100 text-emerald-700';
        case 'inactive':
            return 'border-red-200 bg-red-100 text-red-700';
        default:
            return 'bg-muted text-muted-foreground';
    }
}

function emailVerificationBadgeClass(emailVerifiedAt: string | null) {
    return emailVerifiedAt
        ? 'border-blue-200 bg-blue-100 text-blue-700'
        : 'border-amber-200 bg-amber-100 text-amber-700';
}

function emailVerificationLabel(emailVerifiedAt: string | null) {
    return emailVerifiedAt ? 'Verified' : 'Not Verified';
}


function sortIcon(field: SortField) {
    if (sortBy.value !== field) return RiArrowUpDownLine;
    return sortDir.value === 'asc' ? RiArrowUpSLine : RiArrowDownSLine;
}

function sortIconClass(field: SortField) {
    return sortBy.value === field
        ? 'text-custom-primary'
        : 'text-custom-shadow/40';
}


function visibleRoles(user: User) {
    if (props.canSeeSuperAdmin) return user.roles;
    return user.roles.filter((role) => role.name !== 'super-admin');
}

function isActive(user: User) {
    return user.status === 'active';
}

function initials(name: string) {
    const parts = name.trim().split(/\s+/).filter(Boolean).slice(0, 2);

    return parts.map((part) => part.charAt(0).toUpperCase()).join('') || 'U';
}

function isOwnAccount(user: User) {
    return user.id === props.currentUserId;
}

const previewedUser = ref<User | null>(null);
const openMenus = ref<Record<number, boolean>>({});

function openPreview(user: User) {
    previewedUser.value = user;
}

function selectAdjacentUser(direction: 1 | -1) {
    if (!previewedUser.value) return;

    const list = props.users.data;
    const currentIndex = list.findIndex((u) => u.id === previewedUser.value?.id);
    const nextIndex = currentIndex + direction;
    if (currentIndex === -1 || nextIndex < 0 || nextIndex >= list.length) return;

    openPreview(list[nextIndex]);
}

function handleRowNavigationKeydown(event: KeyboardEvent) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        selectAdjacentUser(1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        selectAdjacentUser(-1);
    }
}

onMounted(() => window.addEventListener('keydown', handleRowNavigationKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleRowNavigationKeydown));


const togglingUser = ref<User | null>(null);
const toggleOpen = ref(false);

function openToggleDialog(user: User) {
    togglingUser.value = user;
    toggleOpen.value = true;
}

const resettingUser = ref<User | null>(null);
const resetOpen = ref(false);

function openResetDialog(user: User) {
    resettingUser.value = user;
    resetOpen.value = true;
}

const archivingUser = ref<User | null>(null);
const archiveOpen = ref(false);

function openArchiveDialog(user: User) {
    archivingUser.value = user;
    archiveOpen.value = true;
}
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
            <MainPanel>
                <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                    <CardHeader class="flex flex-row gap-2">
                        <div class="flex flex-col">
                            <CardTitle class="flex items-center gap-2">
                                <span class="font-semibold">Users</span>
                            </CardTitle>
                            <CardDescription>
                                Manage users, assign roles, and control access.
                            </CardDescription>
                        </div>
                        <div class="flex flex-1 items-center justify-end gap-2">
                            <Button
                                v-if="canCreate"
                                variant="float-primary"
                                class="hidden lg:flex"
                                as-child
                            >
                                <Link :href="create().url" class="flex items-center">
                                    <RiAddLine class="h-4 w-4 shrink-0" />
                                    <span>Add User</span>
                                </Link>
                            </Button>
                            <DropdownMenu v-if="canCreate || canViewTrash" class="w-fit">
                                <DropdownMenuTrigger as-child class="m-0">
                                    <div class="inline-flex">
                                        <Button
                                            variant="header-actions"
                                            class="text-custom-shadow"
                                            size="icon"
                                            aria-label="Open user actions"
                                        >
                                            <RiMore2Line class="h-4 w-4 shrink-0" />
                                        </Button>
                                    </div>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-fit">
                                    <DropdownMenuItem
                                        v-if="canCreate"
                                        as-child
                                        class="cursor-pointer lg:hidden"
                                    >
                                        <Link :href="create().url" class="flex items-center">
                                            <RiAddLine class="h-4 w-4" />
                                            Add User
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-if="canViewTrash"
                                        as-child
                                        class="cursor-pointer group"
                                    >
                                        <Link :href="trash().url" class="flex items-center">
                                            <RiArchive2Line class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow transition-all duration-200" />
                                            Archives
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </CardHeader>
                    <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 pt-2">
                        <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                            <div class="w-full">
                                <SearchInput
                                    :route="index().url"
                                    :initial-value="props.filters.search"
                                    placeholder="Search users..."
                                    :only="[
                                        'users',
                                        'filters',
                                        'statuses',
                                        'flash',
                                    ]"
                                    :debounce="350"
                                    :extra-params="currentFilterParams"
                                />
                            </div>
                            <div
                                class="flex w-fit flex-row gap-2 lg:items-center lg:justify-between"
                            >
                                <div class="flex flex-wrap items-center gap-2">
                                    <Popover v-model:open="filterOpen">
                                        <PopoverTrigger
                                            as-child
                                        >
                                            <Button
                                                variant="header-actions"
                                                size="icon-text"
                                                class="rounded-full"
                                                :class="
                                                    activeFilterCount > 0
                                                        ? 'bg-custom-secondary/20 hover:bg-custom-secondary/80 hover:text-custom-bg-light transition-all duration-200 dark:hover:text-custom-shadow'
                                                        : ''
                                                "
                                            >
                                                <RiFilter2Line class="h-3.5 w-3.5" />
                                                <span class="hidden lg:flex">
                                                    {{
                                                        activeFilterCount > 0
                                                            ? (activeFilterCount === 1 ? '1 filter active' : `${activeFilterCount} filters active`)
                                                            : 'Filter'
                                                    }}
                                                </span>
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent align="end">
                                            <div class="grid gap-y-2">
                                                <div class="flex flex-col gap-y-1">
                                                    <p
                                                        class="text-sm text-custom-shadow/80"
                                                    >
                                                        Type
                                                    </p>
                                                    <Select v-model="pendingRoleFilter">
                                                        <SelectTrigger
                                                            class="w-full"
                                                        >
                                                            <SelectValue
                                                                placeholder="All Roles"
                                                                class="flex justify-start"
                                                            />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem
                                                                value="all"
                                                                class="cursor-pointer text-sm"
                                                            >
                                                                All Types
                                                            </SelectItem>
                                                            <SelectItem
                                                                value="internal"
                                                                class="cursor-pointer text-sm"
                                                            >
                                                                Internal
                                                            </SelectItem>
                                                            <SelectItem
                                                                value="external"
                                                                class="cursor-pointer text-sm"
                                                            >
                                                                External
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>

                                                <div class="flex flex-col gap-y-1">
                                                    <p
                                                        class="text-sm text-custom-shadow/80"
                                                    >
                                                        Status
                                                    </p>
                                                    <Select v-model="pendingStatusFilter">
                                                        <SelectTrigger
                                                            class="w-full"
                                                        >
                                                            <SelectValue
                                                                placeholder="All Statuses"
                                                                class="flex justify-start"
                                                            />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem
                                                                value="all"
                                                                class="cursor-pointer text-sm"
                                                            >
                                                                All Statuses
                                                            </SelectItem>
                                                            <SelectItem
                                                                value="active"
                                                                class="cursor-pointer text-sm"
                                                            >
                                                                Active
                                                            </SelectItem>
                                                            <SelectItem
                                                                value="inactive"
                                                                class="cursor-pointer text-sm"
                                                            >
                                                                Inactive
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">

                                                <div class="flex w-full flex-row items-center justify-between">
                                                    <Button
                                                        v-if="activeFilterCount > 0"
                                                        size="sm"
                                                        variant="destructive"
                                                        @click="clearFilters"
                                                    >
                                                        Clear
                                                    </Button>
                                                    <div class="ml-auto flex items-center gap-2">
                                                        <Button
                                                            variant="ghost-outline"
                                                            size="sm"
                                                            @click="cancelFilterPopover"
                                                        >
                                                            Cancel
                                                        </Button>
                                                        <Button
                                                            variant="float-primary"
                                                            size="sm"
                                                            @click="applyFilterPopover"
                                                        >
                                                            Apply
                                                        </Button>
                                                    </div>
                                                </div>
                                            </div>
                                        </PopoverContent>
                                    </Popover>
                                </div>
                                
                            </div>
                        </div>

                        <TableCard :table-data-length="props.users.data.length">
                            <Table v-if="props.users.data.length > 0">
                                <TableHeader>
                                    <TableColumn class="p-0">
                                        <button
                                            type="button"
                                            class="flex h-10 w-full cursor-pointer select-none items-center justify-start gap-1.5 pl-3 pr-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                            @click="toggleSort('name')"
                                        >
                                            Name & Username
                                            <component
                                                :is="sortIcon('name')"
                                                class="h-3.5 w-3.5"
                                                :class="sortIconClass('name')"
                                            />
                                        </button>
                                    </TableColumn>

                                    <TableColumn>Contact Details</TableColumn>
                                    <TableColumn>Verification</TableColumn>

                                    <TableColumn class="p-0">
                                        <button
                                            type="button"
                                            class="flex h-10 w-full cursor-pointer select-none items-center justify-start gap-1.5 pl-3 pr-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                            @click="toggleSort('status')"
                                        >
                                            Status
                                            <component
                                                :is="sortIcon('status')"
                                                class="h-3.5 w-3.5"
                                                :class="sortIconClass('status')"
                                            />
                                        </button>
                                    </TableColumn>

                                    <TableColumn v-if="showCompanyColumn">Company</TableColumn>
                                    <TableColumn>Roles</TableColumn>
                                </TableHeader>

                                <TableContent>
                                    <TableRow
                                        v-for="(user, rowIndex) in props.users.data"
                                        :key="user.id"
                                        :class="[
                                            rowIndex === props.users.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                            previewedUser?.id === user.id ? 'bg-custom-secondary/10' : '',
                                        ]"
                                        :status="user.status === 'inactive' ? 'inactive' : 'default'"
                                        @click.left="openPreview(user)"
                                        @dblclick="router.visit(show(user.id).url)"
                                    >
                                        <TableData class="pl-3">
                                            <div class="flex min-w-0 items-center gap-2">
                                                <img
                                                    v-if="user.avatar_url"
                                                    :src="user.avatar_url"
                                                    :alt="`${user.name} avatar`"
                                                    class="h-12 w-12 shrink-0 rounded-full object-cover"
                                                />
                                                <div
                                                    v-else
                                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-custom-secondary/20 text-xs font-semibold"
                                                >
                                                    {{ initials(user.name) }}
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="truncate font-semibold capitalize">{{ user.name }}</p>
                                                    <p class="truncate font-mono text-xs text-custom-shadow/70">{{ user.username }}</p>
                                                </div>
                                            </div>
                                        </TableData>

                                        <TableData>
                                            <div class="flex min-w-0 flex-col gap-1 text-sm text-custom-shadow/80">
                                                <span class="truncate">{{ user.email || '—' }}</span>
                                                <span class="truncate">{{ user.phone_number || '—' }}</span>
                                            </div>
                                        </TableData>

                                        <TableData>
                                            <Badge
                                                :class="
                                                    emailVerificationBadgeClass(
                                                        user.email_verified_at,
                                                    )
                                                "
                                                class="border"
                                            >
                                                {{
                                                    emailVerificationLabel(
                                                        user.email_verified_at,
                                                    )
                                                }}
                                            </Badge>
                                        </TableData>

                                        <TableData>
                                            <Badge
                                                :class="
                                                    statusBadgeClass(user.status)
                                                "
                                                class="border capitalize"
                                            >
                                                {{ user.status }}
                                            </Badge>
                                        </TableData>

                                        <TableData v-if="showCompanyColumn" class="text-sm text-custom-shadow/70">
                                            <span class="truncate">
                                                {{
                                                    visibleRoles(user).some(
                                                        (r) => r.type === 'external',
                                                    )
                                                        ? (user.company?.company_name ??
                                                        '-')
                                                        : '-'
                                                }}
                                            </span>
                                        </TableData>

                                        <TableData>
                                            <div
                                                class="flex flex-wrap gap-1 capitalize"
                                            >
                                                <Badge
                                                    v-for="role in visibleRoles(
                                                        user,
                                                    )"
                                                    :key="role.id"
                                                    :class="roleBadgeClass(role)"
                                                    class="border"
                                                >
                                                    {{ role.name }}
                                                </Badge>
                                                <span
                                                    v-if="
                                                        visibleRoles(user)
                                                            .length === 0
                                                    "
                                                    class="text-sm text-custom-shadow/70"
                                                >
                                                    -
                                                </span>
                                            </div>
                                        </TableData>

                                        <TableMoreButton
                                            :open="openMenus[user.id] ?? false"
                                            @update:open="(value) => (openMenus[user.id] = value)"
                                        >
                                            <DropdownMenuLabel class="">
                                                {{ user.username }}
                                            </DropdownMenuLabel>

                                            <DropdownMenuItem
                                                class="group hidden"
                                                @click="show(user.id).url"
                                            >
                                                <RiExternalLinkLine class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-bg transition-all duration-200" />
                                                View
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                as-child
                                                class="group"
                                            >
                                                <Link
                                                    :href="
                                                        show(user.id).url
                                                    "
                                                    class="flex items-center"
                                                >
                                                    <RiExternalLinkLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                                    View
                                                </Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-if="
                                                    canToggle &&
                                                    !isOwnAccount(user)
                                                "
                                                class="group"
                                                @click="
                                                    openToggleDialog(user)
                                                "
                                            >
                                                <RiShutDownLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                                <span class="text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow">{{
                                                    isActive(user)
                                                        ? 'Inactivate'
                                                        : 'Activate'
                                                }}</span>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-if="
                                                    canResetPass &&
                                                    !isOwnAccount(user)
                                                "
                                                class="group"
                                                @click="
                                                    openResetDialog(user)
                                                "
                                            >
                                                <RiKey2Line class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                                <span class="text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow">Reset Password</span>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-if="
                                                    canArchive &&
                                                    !isOwnAccount(user)
                                                "
                                                class="group"
                                                @click="
                                                    openArchiveDialog(user)
                                                "
                                            >
                                                <RiArchive2Line class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                                <span class="text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow">Archive</span>
                                            </DropdownMenuItem>

                                            <Separator v-if="isOwnAccount(user)" class="mt-4"/>

                                            <DropdownMenuItem
                                                v-if="isOwnAccount(user)"
                                                class="pointer-events-none text-custom-shadow/80 text-xs"
                                            >
                                                You cannot manage your own account here.
                                            </DropdownMenuItem>
                                        </TableMoreButton>
                                    </TableRow>
                                </TableContent>
                            </Table>

                            <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                                <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                                    <img
                                        :src="emptyRafikiUrl"
                                        alt=""
                                        class="w-1/3 object-contain opacity-90"
                                        aria-hidden="true"
                                    />
                                    <div class="space-y-1">
                                        <p class="text-custom-shadow text-base font-semibold">No users found</p>
                                        <p class="text-custom-shadow/80 text-sm">
                                            {{ hasActiveFilters ? 'Try adjusting your filters or search.' : 'Try adjusting your search.' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </TableCard>

                        <InertiaPagination
                            :links="props.users.links"
                            :meta="{
                                from: props.users.from,
                                to: props.users.to,
                                total: props.users.total,
                            }"
                        />
                    </CardContent>
                </Card>
            </MainPanel>
           
            <SidePanel>
                <Card class="hidden min-h-0 lg:flex lg:h-full lg:w-full">
                    <CardHeader
                        v-if="previewedUser"
                        class="flex flex-row items-start justify-between gap-3"
                    >
                        <div class="min-w-0">
                            <CardTitle class="truncate capitalize">
                                {{ previewedUser.name }}
                            </CardTitle>
                            <CardDescription>Preview</CardDescription>
                        </div>
                        <Button
                            variant="header-actions"
                            size="icon"
                            class="h-8 w-8 shrink-0 rounded-full"
                            aria-label="Close user preview"
                            @click="previewedUser = null"
                        >
                            <RiCloseLine class="h-4 w-4" />
                        </Button>
                    </CardHeader>

                    <CardContent
                        v-if="previewedUser"
                        class="no-scrollbar min-h-0 flex-1 space-y-4 overflow-y-auto py-2"
                    >
                        <div class="flex flex-col items-center gap-3 rounded-md border border-dashed border-custom-bg-dark bg-custom-bg p-4 dark:border-custom-bg-light dark:bg-custom-bg-dark">
                            <img
                                v-if="previewedUser.avatar_url"
                                :src="previewedUser.avatar_url"
                                :alt="`${previewedUser.name} avatar`"
                                class="h-20 w-20 rounded-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-20 w-20 items-center justify-center rounded-full bg-custom-primary text-xl font-semibold text-white"
                            >
                                {{ initials(previewedUser.name) }}
                            </div>
                            <div class="min-w-0 text-center">
                                <p class="truncate font-semibold text-custom-shadow">
                                    {{ previewedUser.name }}
                                </p>
                                <p class="truncate text-sm text-custom-shadow/70">
                                    @{{ previewedUser.username }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-semibold text-custom-shadow">Status</span>
                                <Badge :class="statusBadgeClass(previewedUser.status)" class="border capitalize">
                                    {{ previewedUser.status }}
                                </Badge>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-semibold text-custom-shadow">Verification</span>
                                <Badge :class="emailVerificationBadgeClass(previewedUser.email_verified_at)" class="border">
                                    {{ emailVerificationLabel(previewedUser.email_verified_at) }}
                                </Badge>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-sm font-semibold text-custom-shadow">Email</span>
                                <span class="min-w-0 truncate text-right text-sm text-custom-shadow/80">
                                    {{ previewedUser.email }}
                                </span>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-sm font-semibold text-custom-shadow">Phone</span>
                                <span class="text-right text-sm text-custom-shadow/80">
                                    {{ previewedUser.phone_number ?? 'Not provided' }}
                                </span>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-sm font-semibold text-custom-shadow">Company</span>
                                <span class="min-w-0 truncate text-right text-sm text-custom-shadow/80">
                                    {{ previewedUser.company?.company_name ?? 'Not assigned' }}
                                </span>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm font-semibold text-custom-shadow">Roles</span>
                                    <span class="text-sm text-custom-shadow/80">
                                        {{ visibleRoles(previewedUser).length }}
                                    </span>
                                </div>
                                <div v-if="visibleRoles(previewedUser).length" class="flex flex-wrap gap-1.5">
                                    <Badge
                                        v-for="role in visibleRoles(previewedUser)"
                                        :key="role.id"
                                        :class="roleBadgeClass(role)"
                                        class="border capitalize"
                                    >
                                        {{ role.name }}
                                    </Badge>
                                </div>
                                <p v-else class="rounded-md bg-custom-bg px-3 py-2 text-sm text-custom-shadow/70 dark:bg-custom-bg-dark">
                                    No roles assigned.
                                </p>
                            </div>
                        </div>

                        <hr class="my-4 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">

                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <Button
                                v-if="canArchive && !isOwnAccount(previewedUser)"
                                variant="destructive"
                                size="icon-text"
                                @click="openArchiveDialog(previewedUser)"
                            >
                                <RiArchive2Line class="h-4 w-4" />
                                Archive
                            </Button>
                            <Button
                                as-child
                                variant="float-primary"
                                size="icon"
                                class="ml-auto"
                            >
                                <Link :href="show(previewedUser.id).url" aria-label="View user profile">
                                    <RiExternalLinkLine class="h-4 w-4" />
                                </Link>
                            </Button>
                        </div>
                    </CardContent>

                    <CardContent
                        v-else
                        class="flex min-h-0 flex-1 items-center justify-center"
                    >
                        <div class="max-w-60 space-y-1 text-center">
                            <p class="text-base font-semibold text-custom-shadow">No user selected</p>
                            <p class="text-sm text-custom-shadow/80">
                                Click on a user to preview.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </SidePanel>

            
        </PanelLayout>

        <ToggleUserStatusDialog v-model:open="toggleOpen" :user="togglingUser" />
        <ResetPasswordDialog v-model:open="resetOpen" :user="resettingUser" />
        <ArchiveUserDialog v-model:open="archiveOpen" :user="archivingUser" />
    </AppLayout>
</template>
