<script setup lang="ts">
/* ======================================================
   Shared UI
====================================================== */
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';

/* shadcn-vue */
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
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
    DropdownMenuSeparator,
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
/* ======================================================
   Layout, Routing & Inertia
====================================================== */
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

/* ======================================================
   Icons
====================================================== */
import {
    RiAddLine,
    RiArchive2Line,
    RiArrowDownSLine,
    RiArrowUpDownLine,
    RiArrowUpSLine,
    RiCloseLine,
    RiEyeLine,
    RiFilter2Line,
    RiKey2Line,
    RiMailLine,
    RiMore2Line,
    RiPencilLine,
    RiShutDownLine,
} from 'vue-remix-icons';

/* ======================================================
   Vue Core
====================================================== */
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

/* ======================================================
   Permissions
====================================================== */
import { can } from '@/lib/can';

const canCreate = can('users.create');
const canUpdate = can('users.update');
const canToggle = can('users.toggleStatus');
const canResetPass = can('users.resetPassword');
const canViewTrash = can('users.viewTrash');

/* ======================================================
   Types
====================================================== */
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

/* ======================================================
   Breadcrumbs
====================================================== */
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: index().url }];

/* ======================================================
   Props
====================================================== */
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

/* ======================================================
   Filter & Sort state
====================================================== */
const roleFilter = ref<string>(props.filters.roles ?? 'all');
const statusFilter = ref<string>(props.filters.status ?? 'all');
const sortBy = ref<SortField>(props.filters.sort_by ?? null);
const sortDir = ref<SortDir>(props.filters.sort_dir ?? 'asc');

const hasActiveFilters = computed(
    () =>
        (roleFilter.value && roleFilter.value !== 'all') ||
        (statusFilter.value && statusFilter.value !== 'all') ||
        sortBy.value !== null,
);

const hasCategoryFilters = computed(
    () =>
        (roleFilter.value && roleFilter.value !== 'all') ||
        (statusFilter.value && statusFilter.value !== 'all'),
);

const activeFilterCount = computed(() => {
    let count = 0;
    if (roleFilter.value && roleFilter.value !== 'all') count++;
    if (statusFilter.value && statusFilter.value !== 'all') count++;
    return count;
});

const filteredUsers = computed(() => {
    let users = props.users.data;

    // Role type filter
    if (roleFilter.value !== 'all') {
        users = users.filter((user) =>
            user.roles?.some((role) => role.type === roleFilter.value),
        );
    }

    // Status filter
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

function onRoleChange(val: string) {
    roleFilter.value = val;
    applyFilters();
}

function onStatusChange(val: string) {
    statusFilter.value = val;
    applyFilters();
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
    sortBy.value = null;
    sortDir.value = 'asc';

    applyFilters({
        role: undefined,
        status: undefined,
        sort_by: undefined,
        sort_dir: undefined,
    });
}

/* ======================================================
   Computed
====================================================== */
const showCompanyColumn = computed(() => roleFilter.value !== 'internal');

/* ======================================================
   Badge helpers
====================================================== */
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

/* ======================================================
   Sort icon helpers
====================================================== */
function sortIcon(field: SortField) {
    if (sortBy.value !== field) return RiArrowUpDownLine;
    return sortDir.value === 'asc' ? RiArrowUpSLine : RiArrowDownSLine;
}

function sortIconClass(field: SortField) {
    return sortBy.value === field
        ? 'text-custom-primary'
        : 'text-custom-shadow/40';
}

/* ======================================================
   Helpers
====================================================== */
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

/* ======================================================
   Actions
====================================================== */
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
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4">
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
                                <Popover>
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
                                            <div class="space-y-2">
                                                <p
                                                    class="text-sm text-custom-shadow/80"
                                                >
                                                    Type
                                                </p>
                                                <Select
                                                    :model-value="roleFilter"
                                                    @update:model-value="
                                                        onRoleChange
                                                    "
                                                >
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

                                            <div class="space-y-2">
                                                <p
                                                    class="text-sm text-custom-shadow/80"
                                                >
                                                    Status
                                                </p>
                                                <Select
                                                    :model-value="statusFilter"
                                                    @update:model-value="
                                                        onStatusChange
                                                    "
                                                >
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
                                            </div>
                                        </div>
                                    </PopoverContent>
                                </Popover>
                            </div>
                            <div
                                class="flex min-w-auto flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                            >
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
                                                <RiMore2Line
                                                    class="h-4 w-4 shrink-0"
                                                />
                                            </Button>
                                        </div>
                                    </DropdownMenuTrigger>

                                    <DropdownMenuContent
                                        align="end"
                                        class="w-fit"
                                    >
                                        <DropdownMenuItem
                                            v-if="canCreate"
                                            as-child
                                            class="cursor-pointer lg:hidden"
                                        >
                                            <Link
                                                :href="create().url"
                                                class="flex items-center"
                                            >
                                                <RiAddLine class="h-4 w-4" />
                                                Add User
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="canViewTrash"
                                            as-child
                                            class="cursor-pointer"
                                        >
                                            <Link
                                                :href="trash().url"
                                                class="flex items-center"
                                            >
                                                <RiArchive2Line class="h-4 w-4" />
                                                Archives
                                            </Link>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                            <!-- <Button
                                    v-if="canCreate"
                                    size="sm"
                                    variant="default"
                                    as-child
                                >
                                    <Link :href="create().url" class="flex items-center gap-1.5">
                                        <RiAddLine class="h-4 w-4" />
                                        New User
                                    </Link>
                                </Button> -->
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
                                        showCompanyColumn ? 'grid-cols-8' : 'grid-cols-7',
                                    ]"
                                >
                                    <button
                                        type="button"
                                        class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 pl-3 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('username')"
                                    >
                                        Username
                                        <component
                                            :is="sortIcon('username')"
                                            class="h-3.5 w-3.5"
                                            :class="sortIconClass('username')"
                                        />
                                    </button>

                                    <button
                                        type="button"
                                        class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('name')"
                                    >
                                        Name
                                        <component
                                            :is="sortIcon('name')"
                                            class="h-3.5 w-3.5"
                                            :class="sortIconClass('name')"
                                        />
                                    </button>

                                    <button
                                        type="button"
                                        class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('email')"
                                    >
                                        Email
                                        <component
                                            :is="sortIcon('email')"
                                            class="h-3.5 w-3.5"
                                            :class="sortIconClass('email')"
                                        />
                                    </button>

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
                                        'grid items-center gap-2 border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light',
                                        showCompanyColumn ? 'grid-cols-8' : 'grid-cols-7',
                                        index === filteredUsers.length - 1 ? 'rounded-b-md border-b-0' : '',
                                    ]"
                                >
                                    <div class="col-span-1 flex min-w-0 justify-start py-1.5 pl-3 font-medium">
                                        <span class="truncate">{{ user.username }}</span>
                                    </div>

                                    <div class="col-span-1 flex min-w-0 justify-start py-1.5">
                                        <span class="truncate">{{ user.name }}</span>
                                    </div>

                                    <div class="col-span-1 flex min-w-0 justify-start py-1.5 text-sm">
                                        <div class="flex min-w-0 items-center gap-1.5 text-custom-shadow/70">
                                            <RiMailLine
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            <span
                                                class="min-w-0 truncate"
                                            >
                                                {{ user.email }}
                                            </span>
                                        </div>
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

                                    <div class="col-span-1 flex justify-end py-1.5 pr-3 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="table-more"
                                                    size="icon-more"
                                                >
                                                    <RiMore2Line
                                                        class="h-4 w-4"
                                                    />
                                                    <span class="sr-only"
                                                        >Open actions</span
                                                    >
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent
                                                align="end"
                                                class="w-fit rounded-lg border-slate-200 shadow-lg"
                                            >
                                                <DropdownMenuLabel
                                                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                                                >
                                                    {{ user.username }}
                                                </DropdownMenuLabel>

                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    as-child
                                                    class="cursor-pointer rounded-lg hover:bg-slate-100"
                                                >
                                                    <Link
                                                        :href="
                                                            show(user.id).url
                                                        "
                                                        class="flex items-center"
                                                    >
                                                        <RiEyeLine class="h-4 w-4" />
                                                        View Profile
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="
                                                        canUpdate &&
                                                        !isOwnAccount(user)
                                                    "
                                                    as-child
                                                >
                                                    <Link
                                                        :href="
                                                            edit(user.id).url
                                                        "
                                                        class="cursor-pointer rounded-lg hover:bg-slate-100"
                                                    >
                                                        <RiPencilLine
                                                            class="h-4 w-4"
                                                        />
                                                        Edit Details
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="
                                                        canToggle &&
                                                        !isOwnAccount(user)
                                                    "
                                                    class="cursor-pointer rounded-lg hover:bg-slate-100"
                                                    @click="
                                                        // handleToggleStatus(user)
                                                        openToggleDialog(user)
                                                    "
                                                >
                                                    <RiShutDownLine class="h-4 w-4" />
                                                    {{
                                                        isActive(user)
                                                            ? 'Set Inactive'
                                                            : 'Set Active'
                                                    }}
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="
                                                        canResetPass &&
                                                        !isOwnAccount(user)
                                                    "
                                                    class="cursor-pointer rounded-lg hover:bg-slate-100"
                                                    @click="
                                                        openResetDialog(user)
                                                    "
                                                >
                                                    <RiKey2Line class="h-4 w-4" />
                                                    Reset Password
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="isOwnAccount(user)"
                                                    disabled
                                                    class="pointer-events-none rounded-lg text-muted-foreground"
                                                >
                                                    You cannot manage your own
                                                    account here
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
                                <Button
                                    v-if="hasActiveFilters"
                                    size="sm"
                                    variant="destructive"
                                    @click="clearFilters"
                                >
                                    <RiCloseLine class="mr-1.5 h-3.5 w-3.5" />
                                    Clear filters
                                </Button>
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
        </div>

        <AlertDialog v-model:open="toggleOpen">
            <AlertDialogContent class="rounded-lg p-4">
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{
                            togglingUser?.status === 'active'
                                ? 'Set User Inactive'
                                : 'Set User Active'
                        }}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to set
                        <span class="font-semibold text-foreground">{{
                            togglingUser?.name ?? 'this user'
                        }}</span>
                        to
                        <span
                            class="font-semibold"
                            :class="
                                togglingUser?.status === 'active'
                                    ? 'text-foreground'
                                    : 'text-foreground'
                            "
                        >
                            {{
                                togglingUser?.status === 'active'
                                    ? 'inactive'
                                    : 'active'
                            }} </span
                        >?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel
                        class="cursor-pointer rounded-lg hover:bg-slate-100"
                        @click="togglingUser = null"
                    >
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        :class="[
                            'cursor-pointer rounded-lg border-0 text-white',
                            togglingUser?.status === 'active'
                                ? 'bg-rose-600 hover:bg-rose-700'
                                : 'bg-primary',
                        ]"
                        @click="confirmToggle"
                    >
                        <RiShutDownLine class="h-4 w-4" />
                        {{
                            togglingUser?.status === 'active'
                                ? 'Set Inactive'
                                : 'Set Active'
                        }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog v-model:open="resetOpen">
            <AlertDialogContent class="rounded-lg p-4">
                <AlertDialogHeader>
                    <AlertDialogTitle>Reset Password</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to reset the password for
                        <span class="font-semibold text-foreground">{{
                            resettingUser?.name ?? 'this user'
                        }}</span
                        >?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel
                        class="cursor-pointer rounded-lg hover:bg-slate-100"
                        @click="resettingUser = null"
                    >
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        class="cursor-pointer rounded-lg border-0 bg-primary text-white hover:bg-primary/90"
                        @click="confirmResetPassword"
                    >
                        <RiKey2Line class="h-4 w-4" />
                        Reset Password
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
