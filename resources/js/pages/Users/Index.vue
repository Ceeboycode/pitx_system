<script setup lang="ts">

import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';


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
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
    edit,
    index,
    resetPassword,
    show,
    toggleStatus,
    trash,
} from '@/routes/users';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';


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
    RiPencilLine,
    RiShutDownLine,
} from 'vue-remix-icons';


import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';


import { can } from '@/lib/can';

const canCreate = can('users.create');
const canUpdate = can('users.update');
const canToggle = can('users.toggleStatus');
const canResetPass = can('users.resetPassword');
const canViewTrash = can('users.viewTrash');


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
        links: any;
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: {
        search?: string | null;
        roles?: string | null;
        status?: string | null;
        sort_by?: SortField;
        sort_dir?: SortDir;
    };
    statuses?: string[];
    canSeeSuperAdmin?: boolean;
    currentUserId: number;
}>();


const roleFilter = ref<string>(props.filters.roles ?? 'all');
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

const filteredUsers = computed(() => {
    let users = props.users.data;

    
    if (roleFilter.value !== 'all') {
        users = users.filter((user) =>
            user.roles?.some((role) => role.type === roleFilter.value),
        );
    }

    
    if (statusFilter.value !== 'all') {
        users = users.filter((user) => user.status === statusFilter.value);
    }

    return users;
});

function applyFilters(
    overrides: Record<string, string | null | undefined> = {},
) {
    router.get(
        index().url,
        {
            search: props.filters.search ?? undefined,
            role: roleFilter.value !== 'all' ? roleFilter.value : undefined,
            status:
                statusFilter.value !== 'all' ? statusFilter.value : undefined,
            sort_by: sortBy.value ?? undefined,
            sort_dir: sortBy.value ? sortDir.value : undefined,
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

function openPreview(user: User) {
    previewedUser.value = user;
}


const togglingUser = ref<User | null>(null);
const toggleOpen = ref(false);

function openToggleDialog(user: User) {
    togglingUser.value = user;
    toggleOpen.value = true;
}

function confirmToggle() {
    if (!togglingUser.value) return;
    router.put(
        toggleStatus(togglingUser.value.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                togglingUser.value = null;
                toggleOpen.value = false;
            },
            onError: () => toast.error('Failed to update user status.'),
        },
    );
}

const resettingUser = ref<User | null>(null);
const resetOpen = ref(false);

function openResetDialog(user: User) {
    resettingUser.value = user;
    resetOpen.value = true;
}

function confirmResetPassword() {
    if (!resettingUser.value) return;
    router.post(
        resetPassword(resettingUser.value.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                resettingUser.value = null;
                resetOpen.value = false;
            },
            onError: () => toast.error('Failed to reset password.'),
        },
    );
}
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4 lg:flex-row lg:items-stretch">
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
                                        <RiArchive2Line class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow transition-all duration-300" />
                                        Archives
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </CardHeader>
                <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                    <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                        <div class="w-full">
                            <SearchInput
                                :route="`${index().url}?type=${roleFilter !== 'all' ? roleFilter : ''}&status=${statusFilter !== 'all' ? statusFilter : ''}&sort_by=${sortBy ?? ''}&sort_dir=${sortBy ? sortDir : ''}`"
                                :initial-value="props.filters.search"
                                placeholder="Search users..."
                                :only="[
                                    'users',
                                    'filters',
                                    'statuses',
                                    'flash',
                                ]"
                                :debounce="350"
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
                                                    ? 'bg-custom-secondary/20 hover:bg-custom-secondary/80 hover:text-custom-bg-light transition-all duration-300 dark:hover:text-custom-shadow'
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

                    <Card
                        :class="[
                            'flex min-h-0 flex-1 max-h-fit flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none',
                            filteredUsers.length === 0 ? 'border-dashed' : 'border-solid',
                        ]"
                    >
                        <div v-if="filteredUsers.length > 0" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                            <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                                <div
                                    :class="[
                                        'grid gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light',
                                        showCompanyColumn
                                            ? 'grid-cols-[minmax(0,2fr)_minmax(0,1.6fr)_minmax(0,0.85fr)_minmax(0,0.75fr)_minmax(0,1fr)_minmax(0,0.7fr)_3rem]'
                                            : 'grid-cols-[minmax(0,2fr)_minmax(0,1.6fr)_minmax(0,0.85fr)_minmax(0,0.75fr)_minmax(0,0.7fr)_3rem]',
                                    ]"
                                >
                                    <button
                                        type="button"
                                        class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 pl-3 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('name')"
                                    >
                                        Name & Username
                                        <component
                                            :is="sortIcon('name')"
                                            class="h-3.5 w-3.5"
                                            :class="sortIconClass('name')"
                                        />
                                    </button>

                                    <div class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">
                                        Contact Details
                                    </div>

                                    <div class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">
                                        Verification
                                    </div>

                                    <button
                                        type="button"
                                        class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('status')"
                                    >
                                        Status
                                        <component
                                            :is="sortIcon('status')"
                                            class="h-3.5 w-3.5"
                                            :class="sortIconClass('status')"
                                        />
                                    </button>

                                    <div
                                        v-if="showCompanyColumn"
                                        class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80"
                                    >
                                        Company
                                    </div>

                                    <div class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">
                                        Roles
                                    </div>

                                    <div class="col-span-1 flex h-10 items-center justify-end px-0 pr-3 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">
                                        Actions
                                    </div>
                                </div>
                            </div>

                            <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                <div
                                    v-for="(user, index) in filteredUsers"
                                    :key="user.id"
                                    :class="[
                                        'grid cursor-pointer items-center gap-2 border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light',
                                        showCompanyColumn
                                            ? 'grid-cols-[minmax(0,2fr)_minmax(0,1.6fr)_minmax(0,0.85fr)_minmax(0,0.75fr)_minmax(0,1fr)_minmax(0,0.7fr)_3rem]'
                                            : 'grid-cols-[minmax(0,2fr)_minmax(0,1.6fr)_minmax(0,0.85fr)_minmax(0,0.75fr)_minmax(0,0.7fr)_3rem]',
                                        index === filteredUsers.length - 1 ? 'rounded-b-md border-b-0' : '',
                                        previewedUser?.id === user.id ? 'bg-custom-secondary/10 text-custom-shadow' : '',
                                    ]"
                                    @click="openPreview(user)"
                                >
                                    <div class="col-span-1 flex min-w-0 items-center gap-2 py-1.5 pl-3">
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

                                    <div class="col-span-1 flex min-w-0 flex-col gap-1 py-1.5 text-sm text-custom-shadow/80">
                                        <span class="truncate">{{ user.email || '—' }}</span>
                                        <span class="truncate">{{ user.phone_number || '—' }}</span>
                                    </div>

                                    <div class="col-span-1 flex justify-start py-1.5">
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
                                    </div>

                                    <div class="col-span-1 flex justify-start py-1.5">
                                        <Badge
                                            :class="
                                                statusBadgeClass(user.status)
                                            "
                                            class="border capitalize"
                                        >
                                            {{ user.status }}
                                        </Badge>
                                    </div>

                                    <div
                                        v-if="showCompanyColumn"
                                        class="col-span-1 flex min-w-0 justify-start py-1.5 text-sm text-custom-shadow/70"
                                    >
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
                                    </div>

                                    <div class="col-span-1 flex min-w-0 justify-start py-1.5">
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
                                    </div>

                                    <div class="col-span-1 flex justify-end py-1.5 pr-3 text-right" @click.stop>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="table-more"
                                                    size="icon-more"
                                                >
                                                    <RiMore2Line
                                                        class="h-4 w-4"
                                                    />
                                                    
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end" class="">
                                                <DropdownMenuLabel class="">
                                                    {{ user.username }}
                                                </DropdownMenuLabel>

                                                <DropdownMenuItem
                                                    class="group hidden"
                                                    @click="show(user.id).url"
                                                >
                                                    <RiExternalLinkLine class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-bg transition-all duration-300" />
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
                                                        <RiExternalLinkLine class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                                        View
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="
                                                        canUpdate &&
                                                        !isOwnAccount(user)
                                                    "
                                                    as-child
                                                    class="group"
                                                >
                                                    <Link
                                                        :href="
                                                            edit(user.id).url
                                                        "
                                                        class="flex items-center"
                                                    >
                                                        <RiPencilLine
                                                            class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow"
                                                        />
                                                        Edit
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
                                                    <RiShutDownLine class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                                    <span class="text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow">{{
                                                        isActive(user)
                                                            ? 'Set as Inactive'
                                                            : 'Set as Active'
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
                                                    <RiKey2Line class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                                    <span class="text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow">Reset Password</span>
                                                </DropdownMenuItem>

                                                <Separator v-if="isOwnAccount(user)" class="mt-4"/>

                                                <DropdownMenuItem
                                                    v-if="isOwnAccount(user)"
                                                    class="pointer-events-none text-custom-shadow/80 text-xs"
                                                >
                                                    You cannot manage your own account here.
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                            </div>
                        </div>

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
                    </Card>

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

            <Card class="hidden min-h-0 lg:flex lg:h-full lg:w-100">
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

                    <div class="flex items-center justify-between gap-2">
                        <Button
                            v-if="canUpdate && !isOwnAccount(previewedUser)"
                            as-child
                            variant="ghost-outline"
                            size="icon-text"
                        >
                            <Link :href="edit(previewedUser.id).url">
                                <RiPencilLine class="h-4 w-4" />
                                Edit
                            </Link>
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
        </div>

        <Dialog v-model:open="toggleOpen">
            <DialogContent class="px-6">
                <DialogHeader class="px-0">
                    <DialogTitle>
                        Set user status
                    </DialogTitle>
                    <DialogDescription class="mt-4">
                        Are you sure you want to set
                        <span class="font-semibold text-custom-accent-3">{{
                            togglingUser?.name ?? 'this user'
                        }}</span>
                        as
                        <span class="font-semibold text-custom-accent-3">
                            {{
                                togglingUser?.status === 'active'
                                    ? 'inactive'
                                    : 'active'
                            }} </span
                        >?
                    </DialogDescription>
                </DialogHeader>
                <Separator class="mb-4" />
                <DialogFooter class="gap-2 sm:justify-end">
                    <Button variant="ghost-outline" @click="toggleOpen = false; togglingUser = null">
                        Cancel
                    </Button>
                    <Button
                        :variant="togglingUser?.status === 'active' ? 'destructive' : 'float-primary'"
                        @click="confirmToggle"
                    >
                        <RiShutDownLine class="h-4 w-4" />
                        {{
                            togglingUser?.status === 'active'
                                ? 'Inactivate'
                                : 'Activate'
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="resetOpen">
            <DialogContent class="px-6">
                <DialogHeader class="px-0">
                    <DialogTitle>Reset Password</DialogTitle>
                    <DialogDescription class="mt-4">
                        Are you sure you want to reset the password for
                        <span class="font-semibold text-custom-accent-3">{{
                            resettingUser?.name ?? 'this user'
                        }}</span
                        >?
                    </DialogDescription>
                </DialogHeader>
                <Separator class="mb-4" />
                <DialogFooter class="gap-2 sm:justify-end">
                    <Button variant="ghost-outline" @click="resetOpen = false; resettingUser = null">
                        Cancel
                    </Button>
                    <Button
                        variant="float-primary"
                        @click="confirmResetPassword"
                    >
                        <RiKey2Line class="h-4 w-4" />
                        Reset Password
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
