<script setup lang="ts">
/* ======================================================
   Shared UI
====================================================== */
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';

/* shadcn-vue */
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
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
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

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
    Archive,
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    ChevronRight,
    Eye,
    Filter,
    KeyRound,
    MoreHorizontal,
    Pencil,
    Plus,
    Power,
    Users,
    X,
    Ellipsis
} from 'lucide-vue-next';

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

const filteredUsers = computed(() => {
    let users = props.users.data;

    // Role type filter
    if (roleFilter.value !== 'all') {
        users = users.filter(user =>
            user.roles?.some(role => role.type === roleFilter.value)
        );
    }

    // Status filter
    if (statusFilter.value !== 'all') {
        users = users.filter(user =>
            user.status === statusFilter.value
        );
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
    if (sortBy.value !== field) return ArrowUpDown;
    return sortDir.value === 'asc' ? ArrowUp : ArrowDown;
}

function sortIconClass(field: SortField) {
    return sortBy.value === field
        ? 'text-blue-600'
        : 'text-muted-foreground/40';
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
function handleToggleStatus(user: User) {
    const actionLabel = isActive(user) ? 'set inactive' : 'set active';
    if (!confirm(`Are you sure you want to ${actionLabel} ${user.name}?`)) {
        toast.info('Status update cancelled.');
        return;
    }

    router.put(
        toggleStatus(user.id).url,
        {},
        {
            preserveScroll: true,
            onError: () => toast.error('Failed to update user status.'),
        },
    );
}

function handleResetPassword(user: User) {
    if (!confirm(`Reset password for ${user.name}?`)) {
        toast.info('Password reset cancelled.');
        return;
    }

    router.post(
        resetPassword(user.id).url,
        {},
        {
            preserveScroll: true,
            onError: () => toast.error('Failed to reset password.'),
        },
    );
}

</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        Users
                        <div class="ml-2 flex flex-1 items-center">
                            <hr class="h-px w-full border border-rose-500 " />
                            <div class="border-7 border-rose-500 rounded-xs">
                                <div class="border-3 border-white rounded-xs"></div>
                            </div>
                        </div>
                    </CardTitle>
                    <CardDescription class="mt-1">
                        Manage users, assign roles, and control access.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="w-50/100">
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
                                class="shadow-sm rounded-lg "
                            />
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between min-w-50/100">
                            <div class="flex flex-wrap items-center gap-2">
                                <Popover>
                                    <PopoverTrigger as-child class="cursor-pointer h-full w-fit rounded-lg border-slate-200 shadow-sm">
                                        <Button
                                            variant="outline"
                                            class="rounded-lg border-slate-200 px-3 text-slate-600 shadow-sm hover:bg-slate-100"
                                        >
                                            <Filter class="h-3.5 w-3.5" />
                                            {{
                                                hasCategoryFilters
                                                    ? 'Filters Active'
                                                    : 'Filters'
                                            }}
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent
                                        align="start"
                                        class="w-80 rounded-lg border-slate-200 p-4 shadow-lg"
                                    >
                                        <div class="grid gap-y-4">
                                            <div class="space-y-2">
                                                <p
                                                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                                                >
                                                    Type
                                                </p>
                                                <Select
                                                    :model-value="roleFilter"
                                                    @update:model-value="onRoleChange"
                                                >
                                                    <SelectTrigger
                                                        class="cursor-pointer h-8 w-full rounded-lg border-slate-200 shadow-sm"
                                                    >
                                                        <SelectValue
                                                            placeholder="All Roles"
                                                            class="flex justify-start"
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent class="rounded-lg shadow-lg">
                                                        <SelectItem value="all" class="cursor-pointer text-sm">
                                                            All Types
                                                        </SelectItem>
                                                        <SelectItem value="internal" class="cursor-pointer text-sm">
                                                            Internal
                                                        </SelectItem>
                                                        <SelectItem value="external" class="cursor-pointer text-sm">
                                                            External
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div class="space-y-2">
                                                <p
                                                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                                                >
                                                    Status
                                                </p>
                                                <Select
                                                    :model-value="statusFilter"
                                                    @update:model-value="onStatusChange"
                                                >
                                                    <SelectTrigger
                                                        class="cursor-pointer h-8 w-full rounded-lg border-slate-200 shadow-sm"
                                                    >
                                                        <SelectValue
                                                            placeholder="All Statuses"
                                                            class="flex justify-start"
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent class="rounded-lg shadow-lg">
                                                        <SelectItem value="all" class="cursor-pointer text-sm">
                                                            All Statuses
                                                        </SelectItem>
                                                        <SelectItem value="active" class="cursor-pointer text-sm">
                                                            Active
                                                        </SelectItem>
                                                        <SelectItem value="inactive" class="cursor-pointer text-sm">
                                                            Inactive
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div class="flex justify-end">
                                                <Button
                                                    v-if="hasCategoryFilters"
                                                    size="sm"
                                                    variant="ghost"
                                                    class="h-8 rounded-lg px-2 text-xs text-muted-foreground hover:text-rose-600"
                                                    @click="clearFilters"
                                                >
                                                    <X class="mr-1 h-3.5 w-3.5" />
                                                    Clear filters
                                                </Button>
                                            </div>
                                        </div>
                                    </PopoverContent>
                                </Popover>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between min-w-auto">
                                <DropdownMenu
                                    v-if="
                                        canCreate
                                    "
                                    class="w-fit"
                                >
                                    <DropdownMenuTrigger as-child class="m-0">
                                        <div
                                            class="inline-flex rounded-lg border border-slate-200 bg-white shadow-sm"
                                        >
                                            <Button
                                                variant="ghost"
                                                class="rounded-lg cursor-pointer group/segment border-0 px-3 text-slate-600 shadow-none transition-all duration-300 hover:bg-slate-100 focus-visible:z-10 gap-0"
                                            >
                                                <Ellipsis
                                                    class="h-4 w-4 shrink-0"
                                                />
                                                <span
                                                    class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-300 group-hover/segment:ml-2 group-hover/segment:max-w-20 group-hover/segment:opacity-100 group-focus-visible/segment:ml-2 group-focus-visible/segment:max-w-20 group-focus-visible/segment:opacity-100"
                                                >
                                                    Actions
                                                </span>
                                            </Button>
                                        </div>
                                    </DropdownMenuTrigger>

                                    <DropdownMenuContent
                                        align="end"
                                        class="w-fit rounded-lg shadow-lg"
                                    >
                                        <DropdownMenuItem
                                            v-if="canCreate || canViewTrash"
                                            as-child
                                            class="cursor-pointer rounded-lg text-slate-700 focus:bg-slate-100 focus:text-slate-900"
                                        >
                                            <Link
                                                :href="create().url"
                                                class="flex items-center"
                                            >
                                                <Plus class="h-4 w-4" />
                                                Create User
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            as-child
                                            class="cursor-pointer rounded-lg text-slate-700 focus:bg-slate-100 focus:text-slate-900"
                                        >
                                            <Link
                                                :href="trash().url"
                                                class="flex items-center"
                                            >
                                                <Archive class="h-4 w-4" />
                                                Archives
                                            </Link>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                            <!-- <Button
                                    v-if="canCreate"
                                    size="sm"
                                    variant="blue"
                                    as-child
                                >
                                    <Link :href="create().url" class="flex items-center gap-1.5">
                                        <Plus class="h-4 w-4" />
                                        New User
                                    </Link>
                                </Button> -->
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 hover:bg-muted/40">
                                    <TableHead
                                        class="cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
                                        @click="toggleSort('username')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Username
                                            <component
                                                :is="sortIcon('username')"
                                                class="h-3.5 w-3.5"
                                                :class="
                                                    sortIconClass('username')
                                                "
                                            />
                                        </div>
                                    </TableHead>

                                    <TableHead
                                        class="cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
                                        @click="toggleSort('name')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Name
                                            <component
                                                :is="sortIcon('name')"
                                                class="h-3.5 w-3.5"
                                                :class="sortIconClass('name')"
                                            />
                                        </div>
                                    </TableHead>

                                    <TableHead
                                        class="cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
                                        @click="toggleSort('email')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Email
                                            <component
                                                :is="sortIcon('email')"
                                                class="h-3.5 w-3.5"
                                                :class="sortIconClass('email')"
                                            />
                                        </div>
                                    </TableHead>

                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                    >
                                        Verification
                                    </TableHead>

                                    <TableHead
                                        class="cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
                                        @click="toggleSort('status')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Status
                                            <component
                                                :is="sortIcon('status')"
                                                class="h-3.5 w-3.5"
                                                :class="sortIconClass('status')"
                                            />
                                        </div>
                                    </TableHead>

                                    <TableHead
                                        v-if="showCompanyColumn"
                                        class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                    >
                                        Company
                                    </TableHead>

                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                    >
                                        Roles
                                    </TableHead>

                                    <TableHead
                                        class="text-right text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                    >
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow
                                    v-if="filteredUsers.length === 0"
                                    class="hover:bg-transparent"
                                >
                                    <TableCell
                                        :colspan="showCompanyColumn ? 8 : 7"
                                        class="py-20 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center gap-3"
                                        >
                                            <div
                                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted"
                                            >
                                                <Users
                                                    class="h-6 w-6 text-muted-foreground/40"
                                                />
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-foreground"
                                                >
                                                    No users found
                                                </p>
                                                <p
                                                    class="mt-0.5 text-xs text-muted-foreground"
                                                >
                                                    {{
                                                        hasActiveFilters
                                                            ? 'Try adjusting your filters or search.'
                                                            : 'Try adjusting your search.'
                                                    }}
                                                </p>
                                            </div>
                                            <Button
                                                v-if="hasActiveFilters"
                                                size="sm"
                                                variant="outline"
                                                class="mt-1 h-8 rounded-lg text-xs"
                                                @click="clearFilters"
                                            >
                                                <X class="mr-1.5 h-3.5 w-3.5" />
                                                Clear filters
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <!-- <TableRow
                                    v-for="user in props.users.data"
                                    :key="user.id"
                                    class="group transition-colors hover:bg-muted/30"
                                > -->
                                <TableRow
                                    v-for="user in filteredUsers"
                                    :key="user.id"
                                    class="group transition-colors hover:bg-muted/30"
                                >
                                    <TableCell class="font-medium">
                                        {{ user.username }}
                                    </TableCell>

                                    <TableCell>
                                        {{ user.name }}
                                    </TableCell>

                                    <TableCell
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{ user.email }}
                                    </TableCell>

                                    <TableCell>
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
                                    </TableCell>

                                    <TableCell>
                                        <Badge
                                            :class="
                                                statusBadgeClass(user.status)
                                            "
                                            class="border capitalize"
                                        >
                                            {{ user.status }}
                                        </Badge>
                                    </TableCell>

                                    <TableCell
                                        v-if="showCompanyColumn"
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{
                                            visibleRoles(user).some(
                                                (r) => r.type === 'external',
                                            )
                                                ? (user.company?.company_name ??
                                                  '-')
                                                : '-'
                                        }}
                                    </TableCell>

                                    <TableCell>
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
                                                class="text-sm text-muted-foreground"
                                            >
                                                -
                                            </span>
                                        </div>
                                    </TableCell>

                                    <TableCell class="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-8 w-8 rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground"
                                                >
                                                    <MoreHorizontal
                                                        class="h-4 w-4"
                                                    />
                                                    <span class="sr-only"
                                                        >Open actions</span
                                                    >
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent
                                                align="end"
                                                class="w-52 rounded-xl border-slate-200 shadow-lg"
                                            >
                                                <DropdownMenuLabel
                                                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                                                >
                                                    {{ user.username }}
                                                </DropdownMenuLabel>

                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    as-child
                                                    class="rounded-lg text-blue-700 focus:bg-blue-50 focus:text-blue-700"
                                                >
                                                    <Link
                                                        :href="
                                                            show(user.id).url
                                                        "
                                                        class="flex items-center"
                                                    >
                                                        <Eye
                                                            class="mr-2 h-4 w-4"
                                                        />
                                                        View Profile
                                                        <ChevronRight
                                                            class="ml-auto h-3.5 w-3.5 text-blue-400"
                                                        />
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
                                                        class="rounded-lg text-amber-600 focus:bg-amber-50 focus:text-amber-700"
                                                    >
                                                        <Pencil
                                                            class="mr-2 h-4 w-4"
                                                        />
                                                        Edit Details
                                                        <ChevronRight
                                                            class="ml-auto h-3.5 w-3.5 text-amber-400"
                                                        />
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="
                                                        canToggle &&
                                                        !isOwnAccount(user)
                                                    "
                                                    class="rounded-lg text-rose-700 focus:bg-rose-50 focus:text-rose-700"
                                                    @click="
                                                        handleToggleStatus(user)
                                                    "
                                                >
                                                    <Power
                                                        class="mr-2 h-4 w-4"
                                                    />
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
                                                    class="rounded-lg text-blue-700 focus:bg-blue-50 focus:text-blue-700"
                                                    @click="
                                                        handleResetPassword(
                                                            user,
                                                        )
                                                    "
                                                >
                                                    <KeyRound
                                                        class="mr-2 h-4 w-4"
                                                    />
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
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

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
    </AppLayout>
</template>
